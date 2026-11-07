<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint as MongoBlueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        $schema = Schema::connection('mongodb');

        if (! $schema->hasTable('scan_events')) {
            $schema->create('scan_events', function (Blueprint $collection): void {
                $collection->unique('event_id');
                $collection->index('recorded_at');
            });
        }

        $schema->table('scan_events', function (Blueprint $collection): void {
            if (! $collection instanceof MongoBlueprint) {
                return;
            }

            $collection->jsonSchema([
                'bsonType' => 'object',
                'required' => ['event_id', 'device_id', 'symbology', 'payload', 'recorded_at'],
                'properties' => [
                    'event_id' => ['bsonType' => 'string', 'maxLength' => 64],
                    'device_id' => ['bsonType' => 'string', 'maxLength' => 64],
                    'symbology' => ['bsonType' => 'string', 'maxLength' => 32],
                    'payload' => ['bsonType' => 'string', 'maxLength' => 512],
                    'warehouse_code' => ['bsonType' => ['string', 'null'], 'maxLength' => 32],
                    'recorded_at' => ['bsonType' => 'date'],
                    'device' => [
                        'bsonType' => ['object', 'null'],
                        'properties' => [
                            'model' => ['bsonType' => 'string', 'maxLength' => 64],
                            'firmware' => ['bsonType' => 'string', 'maxLength' => 32],
                        ],
                    ],
                ],
            ], 'strict', 'error');
        });
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('scan_events');
    }
};
