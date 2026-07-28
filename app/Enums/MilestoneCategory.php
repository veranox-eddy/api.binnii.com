<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MilestoneCategory: string
{
    use HasValues;

    case Firsts = 'firsts';
    case Physical = 'physical';
    case Cognitive = 'cognitive';
    case Language = 'language';
    case Social = 'social';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
