<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum EnrollmentStatus: string
{
    use HasValues;

    case Active = 'active';
    case Upcoming = 'upcoming';
    case Graduated = 'graduated';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
