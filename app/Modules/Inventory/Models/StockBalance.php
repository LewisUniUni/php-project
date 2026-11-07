<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $sku_id
 * @property string $sku_code
 * @property string $location_id
 * @property string $location_code
 * @property int $on_hand
 * @property int $reserved
 * @property int $version
 */
final class StockBalance extends Model
{
    use HasUuids;

    protected $fillable = [
        'sku_id',
        'sku_code',
        'location_id',
        'location_code',
        'on_hand',
        'reserved',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'on_hand' => 'integer',
            'reserved' => 'integer',
            'version' => 'integer',
        ];
    }
}
