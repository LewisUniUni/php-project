<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Actions\RegisterLocation;
use App\Modules\Catalog\Actions\RegisterSku;
use App\Modules\Identity\Role;
use App\Support\Values\ConfiguredString;
use App\Support\Values\Identifier;
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
                'password' => Hash::make(ConfiguredString::get('seed.operator_password', 'local-operator-password')),
                'role' => Role::Operator,
                'warehouse_code' => 'WH-1',
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'blair@quay.local'],
            [
                'name' => 'Blair Okonkwo',
                'password' => Hash::make(ConfiguredString::get('seed.planner_password', 'local-planner-password')),
                'role' => Role::Planner,
                'warehouse_code' => 'WH-1',
            ],
        );

        app(RegisterSku::class)->execute('SKU-PALLET-WRAP', 'Pallet wrap, 80 gauge', 'each');
        app(RegisterLocation::class)->execute('LOC-A-01', 'Aisle A, bay 01', 'A');

        $clients = app(ClientRepository::class);
        $definitions = [
            ['warehouse-api', ConfiguredString::get('seed.warehouse_client_secret', 'local-warehouse-client-secret'), ['inventory.read', 'inventory.write', 'delivery.read', 'delivery.write']],
            ['driver-mobile', ConfiguredString::get('seed.driver_client_secret', 'local-driver-client-secret'), ['delivery.read', 'delivery.write', 'telemetry.write']],
            ['pda-scanner', ConfiguredString::get('seed.pda_client_secret', 'local-pda-client-secret'), ['inventory.read', 'telemetry.write']],
            ['partner:acme', ConfiguredString::get('seed.partner_client_secret', 'local-partner-client-secret'), ['inventory.read', 'delivery.read']],
        ];

        foreach ($definitions as [$name, $secret, $scopes]) {
            $existing = Client::query()->where('name', $name)->first();
            if ($existing !== null) {
                continue;
            }

            $client = $clients->createClientCredentialsGrantClient($name);
            $client->forceFill([
                'secret' => $secret,
                'scopes' => $scopes,
            ])->save();
            $this->command?->info($name.' client '.Identifier::string($client->getKey()));
        }
    }
}
