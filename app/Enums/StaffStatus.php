<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum StaffStatus: string
{
    use HasValues;

    case Active = 'active';
    case Upcoming = 'upcoming';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Upcoming => 'Upcoming',
            self::Deactivated => 'Deactivated',
        };
    }
}
