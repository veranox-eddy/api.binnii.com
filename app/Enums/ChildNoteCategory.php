<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Union of the category options offered by the two wireframe forms
 * (db-add-child.html and me-editprofile.html) — see schema doc.
 */
enum ChildNoteCategory: string
{
    use HasValues;

    case SpecialInstructions = 'special_instructions';
    case Schedule = 'schedule';
    case FavoriteThings = 'favorite_things';
    case Other = 'other';
    case Msp = 'msp';
    case Insurance = 'insurance';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::SpecialInstructions => 'Special Instructions',
            self::Schedule => 'Schedule',
            self::FavoriteThings => 'Favorite Things',
            self::Other => 'Other',
            self::Msp => 'MSP',
            self::Insurance => 'Insurance',
            self::General => 'General',
        };
    }
}
