<?php

namespace App\Models;

use App\Enums\MenusCalendarType;
use Database\Factories\MenusCalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['center_id', 'type', 'name', 'parent_visible'])]
class MenusCalendar extends Model
{
    /** @use HasFactory<MenusCalendarFactory> */
    use HasFactory;

    protected $table = 'menus_calendars';

    protected function casts(): array
    {
        return [
            'type' => MenusCalendarType::class,
            'parent_visible' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'menus_calendar_id')->orderBy('event_date');
    }
}
