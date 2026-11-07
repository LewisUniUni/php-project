<?php

declare(strict_types=1);

namespace App\Modules\Identity;

enum Role: string
{
    case Operator = 'operator';
    case Planner = 'planner';
}
