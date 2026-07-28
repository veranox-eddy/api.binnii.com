<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ResponseItemType: string
{
    use HasValues;

    case Permission = 'permission';
    case EConsent = 'e_consent';
    case Document = 'document';
}
