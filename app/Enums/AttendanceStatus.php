<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AttendanceStatus: string
{
    use HasValues;

    case Present = 'present';
    case CheckedOut = 'checked_out';
    case Absent = 'absent';
    case Moved = 'moved';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::CheckedOut => 'Checked out',
            self::Absent => 'Absent',
            self::Moved => 'Moved',
        };
    }
}
