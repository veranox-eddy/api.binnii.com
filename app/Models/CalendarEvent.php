<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['menus_calendar_id', 'event_date', 'title', 'description'])]
class CalendarEvent extends Model
{
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(MenusCalendar::class, 'menus_calendar_id');
    }
}
