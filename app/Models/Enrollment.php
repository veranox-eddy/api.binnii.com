<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\Rotation;
use App\Support\Weekdays;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['child_id', 'classroom_id', 'status', 'rotation', 'enrolled_on', 'graduated_on'])]
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'rotation' => Rotation::class,
            'enrolled_on' => 'date',
            'graduated_on' => 'date',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(EnrollmentDay::class);
    }

    /**
     * Daychip labels (Mon…) for the enrolled weekdays.
     *
     * @return Collection<int, string>
     */
    public function dayNames(): Collection
    {
        return $this->days->pluck('weekday')->map(fn (int $day) => Weekdays::MAP[$day]);
    }

    /**
     * Compact schedule code as shown on the profile page, e.g. "MTWRF".
     */
    public function scheduleCode(): string
    {
        return collect(Weekdays::LETTERS)
            ->filter(fn (string $letter, int $day) => $this->days->pluck('weekday')->contains($day))
            ->implode('');
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

    public function graduate(): void
    {
        // Idempotent: re-graduating must not overwrite the original date.
        if ($this->status === EnrollmentStatus::Graduated) {
            return;
        }

        $this->update([
            'status' => EnrollmentStatus::Graduated,
            'graduated_on' => now()->toDateString(),
        ]);
    }

    public function activate(): void
    {
        // Idempotent, and only ever promotes a scheduled row: a double submit
        // must not resurrect a graduated enrollment. enrolled_on is left as
        // planned — activating early does not rewrite the agreed start date.
        if ($this->status !== EnrollmentStatus::Scheduled) {
            return;
        }

        $this->update(['status' => EnrollmentStatus::Active]);
    }
}
