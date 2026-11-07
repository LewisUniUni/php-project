<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_stops', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference', 64)->unique();
            $table->foreignUuid('reservation_id')->nullable()->constrained('reservations');
            $table->string('warehouse_code', 32);
            $table->string('sku_code', 64);
            $table->unsignedInteger('quantity');
            $table->string('status', 32);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();

            $table->index(['warehouse_code', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_stops');
    }
};
