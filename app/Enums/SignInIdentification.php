<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum SignInIdentification: string
{
    use HasValues;

    case None = 'none';
    case NameInitials = 'name_initials';
    case ESignature = 'e_signature';

    /** Option wording per logins-config.html. */
    public function label(): string
    {
        return match ($this) {
            self::None => 'No Identification Collected',
            self::NameInitials => 'Name/Initials (Typed)',
            self::ESignature => 'Electronic Signature',
        };
    }
}
