<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AllergySeverity: string
{
    use HasValues;

    case Minor = 'minor';
    case Moderate = 'moderate';
    case Severe = 'severe';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
