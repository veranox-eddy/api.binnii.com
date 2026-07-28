<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ScreeningAdministeredBy: string
{
    use HasValues;

    case Staff = 'staff';
    case Family = 'family';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
