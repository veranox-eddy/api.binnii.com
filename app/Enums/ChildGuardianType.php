<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ChildGuardianType: string
{
    use HasValues;

    case Parent = 'parent';
    case Guardian = 'guardian';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
