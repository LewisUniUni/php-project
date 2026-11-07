<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sku_id')->constrained('skus');
            $table->string('sku_code', 64);
            $table->foreignUuid('location_id')->constrained('locations');
            $table->string('location_code', 64);
            $table->unsignedInteger('on_hand')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->unique(['sku_id', 'location_id']);
            $table->index('location_code');
        });

        Schema::create('reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sku_id')->constrained('skus');
            $table->string('sku_code', 64);
            $table->foreignUuid('location_id')->constrained('locations');
            $table->string('location_code', 64);
            $table->unsignedInteger('quantity');
            $table->string('status', 32);
            $table->string('reference', 64);
            $table->string('warehouse_code', 32);
            $table->string('idempotency_key', 64)->unique();
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->index(['sku_id', 'location_id', 'status']);
            $table->index('reference');
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('sku_id')->constrained('skus');
            $table->string('sku_code', 64);
            $table->foreignUuid('location_id')->constrained('locations');
            $table->string('location_code', 64);
            $table->string('type', 32);
            $table->unsignedInteger('quantity');
            $table->uuid('reservation_id')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('actor_type', 32);
            $table->string('actor_id', 64);
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sku_id', 'location_id', 'created_at']);
            $table->foreign('reservation_id')->references('id')->on('reservations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('stock_balances');
    }
};
