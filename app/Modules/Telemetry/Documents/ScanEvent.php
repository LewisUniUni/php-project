<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Documents;

use App\Support\Database\CappedMongoBuilder;
use App\Support\Eloquent\ReadsColumns;
use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Variable scanner metadata belongs in a document. Stock quantity does not.
 *
 * @property string $event_id
 * @property string $device_id
 * @property string $symbology
 * @property string $payload
 * @property string|null $warehouse_code
 * @property Carbon $recorded_at
 * @property array{model?: string, firmware?: string}|null $device
 */
final class ScanEvent extends Model
{
    use ReadsColumns;

    protected $connection = 'mongodb';

    /** @var string */
    protected $collection = 'scan_events';

    public $timestamps = false;

    protected $guarded = [];

    public static function readColumns(): array
    {
        return ['_id', 'event_id', 'device_id', 'symbology', 'payload', 'warehouse_code', 'recorded_at', 'device'];
    }

    public function newEloquentBuilder($query)
    {
        return new CappedMongoBuilder($query);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }
}
