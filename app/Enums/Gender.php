<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum Gender: string
{
    use HasValues;

    case Male = 'male';
    case Female = 'female';
    case X = 'x';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::X => 'X',
        };
    }
}
