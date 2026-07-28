<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ChildGender: string
{
    use HasValues;

    case Boy = 'boy';
    case Girl = 'girl';
    case X = 'x';

    public function label(): string
    {
        return match ($this) {
            self::Boy => 'Boy',
            self::Girl => 'Girl',
            self::X => 'X',
        };
    }
}
