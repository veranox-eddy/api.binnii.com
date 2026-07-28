<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ConversationType: string
{
    use HasValues;

    case Message = 'message';
    case Notice = 'notice';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
