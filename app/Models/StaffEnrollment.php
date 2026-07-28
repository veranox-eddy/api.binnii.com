<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\Rotation;
use App\Support\Weekdays;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['staff_id', 'classroom_id', 'status', 'rotation', 'start_date', 'end_date'])]
class StaffEnrollment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'rotation' => Rotation::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(StaffEnrollmentDay::class);
    }

    /**
     * Daychip labels (Mon…) for the scheduled weekdays.
     *
     * @return Collection<int, string>
     */
    public function dayNames(): Collection
    {
        return $this->days->pluck('weekday')->map(fn (int $day) => Weekdays::MAP[$day]);
    }

    /**
     * Replace the weekly schedule with the given weekday numbers.
     *
     * @param  array<int, int>  $weekdays
     */
    public function syncDays(array $weekdays): void
    {
        $this->days()->whereNotIn('weekday', $weekdays)->delete();

        foreach ($weekdays as $weekday) {
            $this->days()->firstOrCreate(['weekday' => $weekday]);
        }
    }
}
