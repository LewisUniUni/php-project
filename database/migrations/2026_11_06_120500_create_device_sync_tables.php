<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('http_idempotency_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('actor', 191);
            $table->uuid('idempotency_key');
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status');
            $table->longText('response_body');
            $table->timestamps();

            $table->unique(['actor', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('http_idempotency_keys');
    }
};
