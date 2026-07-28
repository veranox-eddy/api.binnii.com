<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MediaType: string
{
    use HasValues;

    case Photo = 'photo';
    case Video = 'video';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
