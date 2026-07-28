<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum CurriculumLevel: string
{
    use HasValues;

    case None = 'none';
    case Infants = 'infants';
    case Toddlers = 'toddlers';
    case Preschool = 'preschool';

    /** Age-band wording per curr-assign.html. */
    public function label(): string
    {
        return match ($this) {
            self::None => 'No curriculum',
            self::Infants => 'Infants (0-17 months)',
            self::Toddlers => 'Toddlers (18-35 months)',
            self::Preschool => 'Preschool (3-5 years)',
        };
    }
}
