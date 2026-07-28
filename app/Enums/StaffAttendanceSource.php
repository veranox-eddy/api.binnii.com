<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum StaffAttendanceSource: string
{
    use HasValues;

    case Kiosk = 'kiosk';
    case Manual = 'manual';
}
