<?php

namespace App\Support;

/**
 * Weekday number conventions (Carbon: 0 = Sunday … 6 = Saturday), shared by
 * enrollment_days and staff_enrollment_days.
 */
class Weekdays
{
    /** Display order Mon–Sun with daychip labels. */
    public const array MAP = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];

    /** One-letter schedule codes as used on the profile page ("MTWRF"). */
    public const array LETTERS = [1 => 'M', 2 => 'T', 3 => 'W', 4 => 'R', 5 => 'F', 6 => 'S', 0 => 'U'];
}
