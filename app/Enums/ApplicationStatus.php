<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum ApplicationStatus: string
{
    use HasValues;

    case New = 'new';
    case InProgress = 'in_progress';
    case ReadyToReview = 'ready_to_review';
    case Approved = 'approved';
    case Enrolled = 'enrolled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::ReadyToReview => 'Ready to review',
            self::Approved => 'Approved',
            self::Enrolled => 'Enrolled',
            self::Cancelled => 'Cancelled',
        };
    }
}
