<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Support\Eloquent\ReadsColumns;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $zone
 */
final class Location extends Model
{
    use HasUuids;
    use ReadsColumns;

    public static function readColumns(): array
    {
        return ['id', 'code', 'name', 'zone', 'created_at', 'updated_at'];
    }

    protected $fillable = [
        'code',
        'name',
        'zone',
    ];
}
