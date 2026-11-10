<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Delivery\Models\DeliveryStop;
use App\Modules\Inventory\Models\Reservation;
use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Outbox\Models\OutboxMessage;
use App\Modules\Outbox\Models\ProcessedMessage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Studio\Gesso\Laravel\ValidatesOpenApiSchema;
use Tests\TestCase;

final class LogisticsSliceTest extends TestCase
{
    use ValidatesOpenApiSchema;

    public function test_reservation_is_transactional_idempotent_and_relayed_once(): void
    {
        $this->actAsClient(['inventory.read', 'inventory.write', 'delivery.read', 'delivery.write']);
        $this->seedCatalog();

        $receipt = $this->postJson('/api/v1/receipts', [
            'sku_code' => 'SKU-PALLET-WRAP',
            'location_code' => 'LOC-A-01',
            'quantity' => 10,
        ], $this->idempotency());
        $receipt->assertCreated();
        $this->assertResponseMatchesOpenApiSchema($receipt);

        $key = (string) Str::uuid();
        $payload = [
            'sku_code' => 'SKU-PALLET-WRAP',
            'location_code' => 'LOC-A-01',
            'quantity' => 4,
            'reference' => 'DEL-1001',
            'warehouse_code' => 'WH-1',
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();
        $created = $this->postJson('/api/v1/reservations', $payload, ['Idempotency-Key' => $key]);
        $created->assertCreated();
        $this->assertResponseMatchesOpenApiSchema($created);
        $sql = implode(' ', array_column(DB::getQueryLog(), 'query'));
        $this->assertStringContainsString('for update', strtolower($sql));

        $this->assertSame(1, OutboxMessage::query()->count());
        $this->assertSame(1, Reservation::query()->count());
        $balance = StockBalance::query()->firstOrFail();
        $this->assertSame(10, $balance->on_hand);
        $this->assertSame(4, $balance->reserved);

        $replay = $this->postJson('/api/v1/reservations', $payload, ['Idempotency-Key' => $key]);
        $replay->assertCreated();
        $replay->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame(4, StockBalance::query()->firstOrFail()->reserved);

        Artisan::call('queue:work', ['--stop-when-empty' => true, '--tries' => 5]);

        $this->assertSame(1, DeliveryStop::query()->count());
        $this->assertSame('planned', DeliveryStop::query()->firstOrFail()->status->value);
        $this->assertSame(1, ProcessedMessage::query()->count());
        $this->assertSame('processed', OutboxMessage::query()->firstOrFail()->status->value);

        Artisan::call('queue:work', ['--stop-when-empty' => true, '--tries' => 5]);
        $this->assertSame(1, DeliveryStop::query()->count());

        $oversell = $this->postJson('/api/v1/reservations', [
            'sku_code' => 'SKU-PALLET-WRAP',
            'location_code' => 'LOC-A-01',
            'quantity' => 7,
            'reference' => 'DEL-1002',
            'warehouse_code' => 'WH-1',
        ], $this->idempotency());
        $oversell->assertStatus(409);
        $this->assertResponseMatchesOpenApiSchema($oversell);
        $oversell->assertJsonPath('type', 'urn:quay:problem:insufficient-stock');
        $this->assertSame(1, Reservation::query()->count());
        $this->assertSame(0, OutboxMessage::query()->where('payload->reference', 'DEL-1002')->count());
    }

    public function test_delivering_a_stop_consumes_the_reservation(): void
    {
        $this->actAsClient(['inventory.read', 'inventory.write', 'delivery.read', 'delivery.write']);
        $this->seedCatalog();
        $this->postJson('/api/v1/receipts', [
            'sku_code' => 'SKU-PALLET-WRAP',
            'location_code' => 'LOC-A-01',
            'quantity' => 10,
        ], $this->idempotency())->assertCreated();
        $created = $this->postJson('/api/v1/reservations', [
            'sku_code' => 'SKU-PALLET-WRAP',
            'location_code' => 'LOC-A-01',
            'quantity' => 4,
            'reference' => 'DEL-2002',
            'warehouse_code' => 'WH-1',
        ], $this->idempotency())->assertCreated();
        Artisan::call('queue:work', ['--stop-when-empty' => true, '--tries' => 5]);

        $stopId = DeliveryStop::query()->where('reference', 'DEL-2002')->firstOrFail()->id;

        foreach (['dispatched', 'in_transit', 'delivered'] as $status) {
            $response = $this->postJson('/api/v1/delivery-stops/'.$stopId.'/transitions', [
                'status' => $status,
            ], $this->idempotency());
            $response->assertOk();
            $this->assertResponseMatchesOpenApiSchema($response);
        }

        $balance = StockBalance::query()->firstOrFail();
        $this->assertSame(6, $balance->on_hand);
        $this->assertSame(0, $balance->reserved);
        $this->assertSame('consumed', Reservation::query()->firstOrFail()->status->value);
        $this->assertSame(3, StockMovement::query()->count());
        $this->assertSame($created->json('data.id'), Reservation::query()->firstOrFail()->id);
    }

    public function test_missing_and_wrong_scopes_are_rejected(): void
    {
        $this->seedCatalog();

        $missing = $this->getJson('/api/v1/stock-balances');
        $missing->assertUnauthorized();
        $this->assertResponseMatchesOpenApiSchema($missing);
        $missing->assertHeader('Content-Type', 'application/problem+json');

        $this->actAsClient(['delivery.read'], 'driver-mobile');
        $forbidden = $this->getJson('/api/v1/stock-balances');
        $forbidden->assertForbidden();
        $this->assertResponseMatchesOpenApiSchema($forbidden);
    }

    public function test_client_credentials_token_can_read_stock(): void
    {
        $this->seedCatalog();
        $client = $this->client(['inventory.read'], 'warehouse-api');
        $client->forceFill(['secret' => 'local-warehouse-client-secret'])->save();

        $token = $this->post('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->getKey(),
            'client_secret' => 'local-warehouse-client-secret',
            'scope' => 'inventory.read',
        ]);
        $token->assertOk();
        $this->assertResponseMatchesOpenApiSchema($token);

        $accessToken = $token->json('access_token');
        $this->assertIsString($accessToken);

        $this->flushHeaders();
        $this->withToken($accessToken)
            ->getJson('/api/v1/stock-balances')
            ->assertOk();
    }

    /**
     * @return array<string, string>
     */
    private function idempotency(): array
    {
        return ['Idempotency-Key' => (string) Str::uuid()];
    }

    private function seedCatalog(): void
    {
        $this->seed();
    }
}
