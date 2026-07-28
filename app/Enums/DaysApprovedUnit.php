<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum DaysApprovedUnit: string
{
    use HasValues;

    case FullDays = 'full_days';
    case HalfDays = 'half_days';

    public function label(): string
    {
        return match ($this) {
            self::FullDays => 'full days',
            self::HalfDays => 'half days',
        };
    }
}
