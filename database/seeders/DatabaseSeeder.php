<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Actions\RegisterLocation;
use App\Modules\Catalog\Actions\RegisterSku;
use App\Modules\Identity\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'avery@quay.local'],
            [
                'name' => 'Avery Chen',
                'password' => Hash::make((string) env('SEED_OPERATOR_PASSWORD', 'local-operator-password')),
                'role' => Role::Operator,
                'warehouse_code' => 'WH-1',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'blair@quay.local'],
            [
                'name' => 'Blair Okonkwo',
                'password' => Hash::make((string) env('SEED_PLANNER_PASSWORD', 'local-planner-password')),
                'role' => Role::Planner,
                'warehouse_code' => 'WH-1',
            ],
        );

        app(RegisterSku::class)->execute('SKU-PALLET-WRAP', 'Pallet wrap, 80 gauge', 'each');
        app(RegisterLocation::class)->execute('LOC-A-01', 'Aisle A, bay 01', 'A');

        $clients = app(ClientRepository::class);
        $definitions = [
            ['warehouse-api', (string) env('WAREHOUSE_CLIENT_SECRET', 'local-warehouse-client-secret'), ['inventory.read', 'inventory.write', 'delivery.read', 'delivery.write']],
            ['driver-mobile', (string) env('DRIVER_CLIENT_SECRET', 'local-driver-client-secret'), ['delivery.read', 'delivery.write', 'telemetry.write']],
            ['pda-scanner', (string) env('PDA_CLIENT_SECRET', 'local-pda-client-secret'), ['inventory.read', 'telemetry.write']],
            ['partner:acme', (string) env('PARTNER_CLIENT_SECRET', 'local-partner-client-secret'), ['inventory.read', 'delivery.read']],
        ];

        foreach ($definitions as [$name, $secret, $scopes]) {
            $existing = Client::query()->where('name', $name)->first();
            if ($existing !== null) {
                continue;
            }

            $client = $clients->createClientCredentialsGrantClient($name);
            $client->secret = $secret;
            $client->scopes = $scopes;
            $client->save();
            $this->command?->info($name.' client '.$client->getKey());
        }
    }
}
