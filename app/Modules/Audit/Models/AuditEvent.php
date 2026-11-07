<?php

declare(strict_types=1);

namespace App\Modules\Audit\Models;

use App\Support\Eloquent\AppendOnly;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $actor_type
 * @property string $actor_id
 * @property string $action
 * @property string $subject_type
 * @property string $subject_id
 * @property string|null $warehouse_code
 * @property array<string, mixed> $metadata
 */
final class AuditEvent extends Model
{
    use AppendOnly;

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'warehouse_code',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
