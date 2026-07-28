<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

/**
 * The single-table registration funnel (schema decision #5):
 * applicant → waitlist → registration → enrolled (or cancelled).
 */
enum ApplicationStage: string
{
    use HasValues;

    case Applicant = 'applicant';
    case Waitlist = 'waitlist';
    case Registration = 'registration';
    case Enrolled = 'enrolled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'Applicant',
            self::Waitlist => 'Waitlist',
            self::Registration => 'Registration',
            self::Enrolled => 'Enrolled',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Allowed funnel moves via the stage action. Enrolled is never reachable
     * here — only Application::convertToChild() sets it (creating the child).
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Applicant => [self::Waitlist, self::Registration, self::Cancelled],
            self::Waitlist => [self::Applicant, self::Registration, self::Cancelled],
            self::Registration => [self::Waitlist, self::Cancelled],
            self::Enrolled => [],
            self::Cancelled => [self::Applicant],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return $to === $this || in_array($to, $this->allowedTransitions());
    }
}
