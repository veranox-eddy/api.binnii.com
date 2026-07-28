<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PreferredTimeOfDay: string
{
    use HasValues;

    case Mornings = 'mornings';
    case Afternoons = 'afternoons';
    case FullDays = 'full_days';
    case BeforeSchool = 'before_school';
    case AfterSchool = 'after_school';
    case FirstAvailable = 'first_available';

    public function label(): string
    {
        return match ($this) {
            self::Mornings => 'Mornings',
            self::Afternoons => 'Afternoons',
            self::FullDays => 'Full days',
            self::BeforeSchool => 'Before school',
            self::AfterSchool => 'After school',
            self::FirstAvailable => 'First available',
        };
    }
}
