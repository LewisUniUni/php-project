<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $sku_id
 * @property string $sku_code
 * @property string $location_id
 * @property string $location_code
 * @property int $quantity
 * @property ReservationStatus $status
 * @property string $reference
 * @property string $warehouse_code
 * @property string $idempotency_key
 * @property int $version
 */
final class Reservation extends Model
{
    use HasUuids;

    protected $fillable = [
        'sku_id',
        'sku_code',
        'location_id',
        'location_code',
        'quantity',
        'status',
        'reference',
        'warehouse_code',
        'idempotency_key',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => ReservationStatus::class,
            'version' => 'integer',
        ];
    }
}
