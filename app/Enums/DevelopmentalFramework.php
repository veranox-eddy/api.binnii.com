<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum DevelopmentalFramework: string
{
    use HasValues;

    case Age0To3 = 'age_0_3';
    case Age3To6 = 'age_3_6';

    public function label(): string
    {
        return match ($this) {
            self::Age0To3 => '0 - 3 years',
            self::Age3To6 => '3 - 6 years',
        };
    }
}
