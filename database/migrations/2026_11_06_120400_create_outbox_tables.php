<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type', 64);
            $table->json('payload');
            $table->string('status', 32);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('leased_until')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('last_error', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['status', 'leased_until']);
        });

        Schema::create('processed_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('message_id');
            $table->string('consumer', 191);
            $table->timestamp('processed_at')->useCurrent();

            $table->unique(['message_id', 'consumer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_messages');
        Schema::dropIfExists('outbox_messages');
    }
};
