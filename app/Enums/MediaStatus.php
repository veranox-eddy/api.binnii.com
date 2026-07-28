<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MediaStatus: string
{
    use HasValues;

    case Draft = 'draft';
    case Sent = 'sent';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
