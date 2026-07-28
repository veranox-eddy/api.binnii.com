<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MaxAbsentPeriod: string
{
    use HasValues;

    case Week = 'week';
    case Month = 'month';
    case Year = 'year';

    public function label(): string
    {
        return "per {$this->value}";
    }
}
