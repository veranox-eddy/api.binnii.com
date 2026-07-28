<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ChildNameDisplay: string
{
    use HasValues;

    case FullLast = 'full_last';
    case LastInitial = 'last_initial';

    /** Option wording per logins-config.html. */
    public function label(): string
    {
        return match ($this) {
            self::FullLast => 'First Name and Full Last Name (e.g., "Jane Doe")',
            self::LastInitial => 'First Name and Last Initial (e.g., "Jane D.")',
        };
    }
}
