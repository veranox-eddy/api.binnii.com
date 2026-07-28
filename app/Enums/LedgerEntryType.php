<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum LedgerEntryType: string
{
    use HasValues;

    case Charge = 'charge';
    case Payment = 'payment';
    case Credit = 'credit';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
