<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * Compose → "Send via" (db-compose.html).
 */
enum MessageChannel: string
{
    use HasValues;

    case Email = 'email';
    case Sms = 'sms';

    public function label(): string
    {
        return $this === self::Sms ? 'SMS' : 'Email';
    }
}
