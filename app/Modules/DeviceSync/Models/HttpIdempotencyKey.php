<?php

declare(strict_types=1);

namespace App\Modules\DeviceSync\Models;

use App\Support\Eloquent\ReadsColumns;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $actor
 * @property string $idempotency_key
 * @property string $request_hash
 * @property int $response_status
 * @property string $response_body
 */
final class HttpIdempotencyKey extends Model
{
    use HasUuids;
    use ReadsColumns;

    public static function readColumns(): array
    {
        return ['id', 'actor', 'idempotency_key', 'request_hash', 'response_status', 'response_body', 'created_at', 'updated_at'];
    }

    protected $fillable = [
        'actor',
        'idempotency_key',
        'request_hash',
        'response_status',
        'response_body',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
        ];
    }
}
