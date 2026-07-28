<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum GuardianRegistrationStatus: string
{
    use HasValues;

    case Registered = 'registered';
    case Invited = 'invited';
    case NotInvited = 'not_invited';
}
