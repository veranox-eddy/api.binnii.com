<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum DaysApprovedPeriod: string
{
    use HasValues;

    case Week = 'week';
    case Month = 'month';

    public function label(): string
    {
        return "per {$this->value}";
    }
}
