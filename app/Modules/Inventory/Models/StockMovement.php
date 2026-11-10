<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\MovementType;
use App\Support\Eloquent\AppendOnly;
use App\Support\Eloquent\ReadsColumns;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $sku_id
 * @property string $sku_code
 * @property string $location_id
 * @property string $location_code
 * @property MovementType $type
 * @property int $quantity
 * @property string|null $reservation_id
 * @property string|null $idempotency_key
 * @property string $actor_type
 * @property string $actor_id
 */
final class StockMovement extends Model
{
    use AppendOnly;
    use ReadsColumns;

    public static function readColumns(): array
    {
        return ['id', 'sku_id', 'sku_code', 'location_id', 'location_code', 'type', 'quantity', 'reservation_id', 'idempotency_key', 'actor_type', 'actor_id', 'occurred_at', 'created_at'];
    }

    public const UPDATED_AT = null;

    protected $fillable = [
        'sku_id',
        'sku_code',
        'location_id',
        'location_code',
        'type',
        'quantity',
        'reservation_id',
        'idempotency_key',
        'actor_type',
        'actor_id',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
