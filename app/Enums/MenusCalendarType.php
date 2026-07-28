<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MenusCalendarType: string
{
    use HasValues;

    case Calendar = 'calendar';
    case Menu = 'menu';
    case CentralLessonPlan = 'central_lesson_plan';

    public function label(): string
    {
        return match ($this) {
            self::Calendar => 'Calendar',
            self::Menu => 'Menu',
            self::CentralLessonPlan => 'Central Lesson Plan',
        };
    }
}
