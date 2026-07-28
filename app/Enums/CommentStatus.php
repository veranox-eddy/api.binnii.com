<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum CommentStatus: string
{
    use HasValues;

    case Inbox = 'inbox';
    case Archived = 'archived';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
