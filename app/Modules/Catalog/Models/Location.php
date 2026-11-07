<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

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

    protected $fillable = [
        'code',
        'name',
        'zone',
    ];
}
