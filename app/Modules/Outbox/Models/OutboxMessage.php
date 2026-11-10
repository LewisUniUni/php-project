<?php

declare(strict_types=1);

namespace App\Modules\Outbox\Models;

use App\Modules\Outbox\Enums\OutboxStatus;
use App\Support\Eloquent\ReadsColumns;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property OutboxStatus $status
 * @property int $attempts
 * @property Carbon|null $leased_until
 * @property Carbon|null $processed_at
 * @property string|null $last_error
 */
final class OutboxMessage extends Model
{
    use HasUuids;
    use ReadsColumns;

    public static function readColumns(): array
    {
        return ['id', 'type', 'payload', 'status', 'attempts', 'leased_until', 'processed_at', 'last_error', 'created_at'];
    }

    public const UPDATED_AT = null;

    protected $fillable = [
        'type',
        'payload',
        'status',
        'attempts',
        'leased_until',
        'processed_at',
        'last_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => OutboxStatus::class,
            'attempts' => 'integer',
            'leased_until' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
