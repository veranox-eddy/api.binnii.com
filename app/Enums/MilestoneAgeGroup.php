<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MilestoneAgeGroup: string
{
    use HasValues;

    case Prenatal = 'prenatal';
    case Infant = 'infant';
    case Toddler = 'toddler';
    case Preschool = 'preschool';
    case School = 'school';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
