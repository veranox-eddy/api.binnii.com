<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Additional Notes categories offered by db-add-teacher.html — the staff form
 * has no MSP/Insurance/General options, so this is not ChildNoteCategory.
 */
enum StaffNoteCategory: string
{
    use HasValues;

    case SpecialInstructions = 'special_instructions';
    case Schedule = 'schedule';
    case FavoriteThings = 'favorite_things';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SpecialInstructions => 'Special Instructions',
            self::Schedule => 'Schedule',
            self::FavoriteThings => 'Favorite Things',
            self::Other => 'Other',
        };
    }
}
