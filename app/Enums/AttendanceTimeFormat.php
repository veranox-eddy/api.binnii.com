<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum AttendanceTimeFormat: string
{
    use HasValues;

    case TwelveHour = '12h';
    case TwentyFourHour = '24h';
}
