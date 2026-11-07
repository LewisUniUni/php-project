<?php

declare(strict_types=1);

namespace App\Modules\Outbox\Models;

use App\Support\Eloquent\AppendOnly;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $message_id
 * @property string $consumer
 */
final class ProcessedMessage extends Model
{
    use AppendOnly;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'message_id',
        'consumer',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
