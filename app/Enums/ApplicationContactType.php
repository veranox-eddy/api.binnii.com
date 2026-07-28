<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ApplicationContactType: string
{
    use HasValues;

    case Guardian = 'guardian';
    case Emergency = 'emergency';
}
