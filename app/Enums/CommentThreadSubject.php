<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum CommentThreadSubject: string
{
    use HasValues;

    case Notice = 'notice';
    case Incident = 'incident';
    case Note = 'note';
    case Post = 'post';

    public function label(): string
    {
        return match ($this) {
            // What the comment lists actually print for a photo comment
            // (db-comments-list.html / dashboard-wireframe.html).
            self::Post => "Look what I'm doing today!",
            default => ucfirst($this->value),
        };
    }
}
