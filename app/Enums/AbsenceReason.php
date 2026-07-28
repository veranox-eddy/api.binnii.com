<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * The 8 MARK ABSENT reasons — shared by child absences and staff_absences
 * (schema doc). The edit-absence wireframe also lists "Other", which the
 * schema deliberately omits; the enum is law.
 */
enum AbsenceReason: string
{
    use HasValues;

    case Appointment = 'appointment';
    case CenterClosure = 'center_closure';
    case Holiday = 'holiday';
    case HomeDay = 'home_day';
    case NotScheduled = 'not_scheduled';
    case NoShow = 'no_show';
    case Sick = 'sick';
    case Vacation = 'vacation';

    public function label(): string
    {
        return match ($this) {
            self::Appointment => 'Appointment',
            self::CenterClosure => 'Center closure',
            self::Holiday => 'Holiday',
            self::HomeDay => 'Home day',
            self::NotScheduled => 'Not scheduled',
            self::NoShow => 'No-show',
            self::Sick => 'Sick',
            self::Vacation => 'Vacation',
        };
    }

    /** Short code used in attendance grids, e.g. "absent (hd)". */
    public function code(): string
    {
        return match ($this) {
            self::Appointment => 'a',
            self::CenterClosure => 'cc',
            self::Holiday => 'h',
            self::HomeDay => 'hd',
            self::NotScheduled => 'ns',
            self::NoShow => 'n',
            self::Sick => 's',
            self::Vacation => 'v',
        };
    }
}
