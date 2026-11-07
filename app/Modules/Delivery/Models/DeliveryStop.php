<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Models;

use App\Modules\Delivery\Enums\StopStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $reference
 * @property string|null $reservation_id
 * @property string $warehouse_code
 * @property string $sku_code
 * @property int $quantity
 * @property StopStatus $status
 * @property int $version
 */
final class DeliveryStop extends Model
{
    use HasUuids;

    protected $fillable = [
        'reference',
        'reservation_id',
        'warehouse_code',
        'sku_code',
        'quantity',
        'status',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => StopStatus::class,
            'version' => 'integer',
        ];
    }
}
