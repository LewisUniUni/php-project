<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! is_file(storage_path('oauth-private.key')) || ! is_file(storage_path('oauth-public.key'))) {
            $this->artisan('passport:keys', ['--force' => true]);
        }

        foreach (['default', 'cache'] as $connection) {
            Redis::connection($connection)->flushdb();
        }

        $connection = DB::connection('mongodb');
        $this->assertTrue(method_exists($connection, 'getDatabase'));
        $database = $connection->getDatabase();
        $this->assertIsObject($database);
        $this->assertTrue(method_exists($database, 'selectCollection'));
        $collection = $database->selectCollection('scan_events');
        $this->assertIsObject($collection);
        $this->assertTrue(method_exists($collection, 'deleteMany'));
        $collection->deleteMany([]);
    }

    /**
     * @param  list<string>  $scopes
     */
    protected function client(array $scopes, string $name = 'warehouse-api'): Client
    {
        $client = app(ClientRepository::class)->createClientCredentialsGrantClient($name);
        $client->scopes = $scopes;
        $client->save();

        return $client;
    }

    /**
     * @param  list<string>  $scopes
     */
    protected function actAsClient(array $scopes, string $name = 'warehouse-api'): Client
    {
        $client = $this->client($scopes, $name);
        Passport::actingAsClient($client, $scopes);

        return $client;
    }
}
