<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Actions\RegisterLocation;
use App\Modules\Catalog\Actions\RegisterSku;
use App\Modules\Inventory\Models\StockBalance;
use Illuminate\Support\Str;
use Tests\TestCase;

final class WarehouseUiTest extends TestCase
{
    public function test_operator_can_sign_in_and_receive_stock(): void
    {
        app(RegisterSku::class)->execute('SKU-PALLET-WRAP', 'Pallet wrap, 80 gauge');
        app(RegisterLocation::class)->execute('LOC-A-01', 'Aisle A, bay 01', 'A');
        $operator = User::factory()->create([
            'email' => 'avery@quay.local',
            'password' => 'local-operator-password',
        ]);

        $this->get('/login')->assertOk()->assertSee('Sign in to the floor');
        $this->post('/login', [
            'email' => 'avery@quay.local',
            'password' => 'local-operator-password',
        ])->assertRedirect(route('warehouse.stock'));

        $this->actingAs($operator)->get('/warehouse')
            ->assertOk()
            ->assertSee('No stock is on hand yet');

        $this->actingAs($operator)->post('/warehouse/receipts', [
            'sku_code' => 'SKU-PALLET-WRAP',
            'location_code' => 'LOC-A-01',
            'quantity' => 5,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect();

        $this->assertSame(5, StockBalance::query()->firstOrFail()->on_hand);

        $planner = User::factory()->planner()->create();
        $this->actingAs($planner)->post('/warehouse/receipts', [
            'sku_code' => 'SKU-PALLET-WRAP',
            'location_code' => 'LOC-A-01',
            'quantity' => 1,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertForbidden();
    }
}
