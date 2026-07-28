<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum Rotation: string
{
    use HasValues;

    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Day = 'day';
    case BeforeAfterSchool = 'before_after_school';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Morning',
            self::Afternoon => 'Afternoon',
            self::Day => 'Day',
            self::BeforeAfterSchool => 'Before & After School',
        };
    }
}
