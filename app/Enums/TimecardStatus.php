<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum TimecardStatus: string
{
    use HasValues;

    case Open = 'open';
    case Sent = 'sent';
}
