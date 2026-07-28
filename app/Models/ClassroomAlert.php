<?php

namespace App\Models;

use App\Enums\ClassroomAlertType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A classroom's internal reminder (New Alert on the attendance screen) —
 * shown to staff in that room at remind_at, never sent to families.
 */
#[Fillable(['classroom_id', 'type', 'remind_at', 'alert_date', 'staff_id', 'instructions', 'created_by'])]
class ClassroomAlert extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ClassroomAlertType::class,
            'alert_date' => 'date',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Reminder time as shown in the alert list, e.g. "10:15 AM". */
    public function timeForHumans(): string
    {
        return Carbon::parse($this->remind_at)->format('g:i A');
    }
}
