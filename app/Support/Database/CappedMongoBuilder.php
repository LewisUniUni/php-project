<?php

declare(strict_types=1);

namespace App\Support\Database;

use App\Modules\Telemetry\Documents\ScanEvent;
use MongoDB\Laravel\Eloquent\Builder;

/**
 * @extends Builder<ScanEvent>
 */
final class CappedMongoBuilder extends Builder
{
    public function get($columns = [])
    {
        $query = $this->getQuery();
        $selected = is_array($query->columns) && $query->columns !== [] ? array_values($query->columns) : $columns;
        $selected = is_array($selected) ? array_values($selected) : [];
        $groups = is_array($query->groups) ? array_values($query->groups) : [];

        ReadCap::assertMongoFetch($query->limit, $selected, $groups, $query->aggregate);

        return parent::get($columns);
    }
}
