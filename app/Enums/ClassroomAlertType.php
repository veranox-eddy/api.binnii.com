<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * The New Alert types offered on the classroom attendance screen
 * (db-infant-toddler-room.html).
 */
enum ClassroomAlertType: string
{
    use HasValues;

    case Medication = 'medication';
    case Sunscreen = 'sunscreen';
    case DiaperCream = 'diaper_cream';
    case BugSpray = 'bug_spray';
    case Reminder = 'reminder';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Medication => 'Medication',
            self::Sunscreen => 'Sunscreen',
            self::DiaperCream => 'Diaper Cream',
            self::BugSpray => 'Bug Spray',
            self::Reminder => 'Reminder',
            self::Other => 'Other',
        };
    }
}
