<?php

namespace App\Models;

use App\Enums\StaffAttendanceSource;
use App\Enums\TimecardStatus;
use Database\Factories\StaffAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_id', 'work_date', 'clock_in_at', 'clock_out_at', 'source', 'status', 'sent_at'])]
class StaffAttendance extends Model
{
    /** @use HasFactory<StaffAttendanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'source' => StaffAttendanceSource::class,
            'status' => TimecardStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public static function clockIn(Staff $staff, string $date, StaffAttendanceSource $source): self
    {
        // whereDate, not attribute equality: date casts store a time component
        // on some drivers, so firstOrNew would silently create duplicates.
        $attendance = self::where('staff_id', $staff->id)->whereDate('work_date', $date)->first()
            ?? new self(['staff_id' => $staff->id, 'work_date' => $date]);

        $attendance->fill([
            'clock_in_at' => now(),
            'clock_out_at' => null,
            'source' => $source,
        ])->save();

        return $attendance;
    }

    public function clockOut(): void
    {
        $this->update(['clock_out_at' => now()]);
    }

    /** Mark the time card sent (staff-timecards.html Send / Resend). */
    public function markSent(): void
    {
        $this->update(['status' => TimecardStatus::Sent, 'sent_at' => now()]);
    }

    /** Re-open a sent time card. */
    public function reopen(): void
    {
        $this->update(['status' => TimecardStatus::Open, 'sent_at' => null]);
    }

    /** Minutes between clock-in and clock-out; 0 while the shift is open. */
    public function minutesWorked(): int
    {
        return $this->clock_out_at?->gt($this->clock_in_at)
            ? (int) $this->clock_in_at->diffInMinutes($this->clock_out_at)
            : 0;
    }

    /** Clocked in and not yet out. */
    public function isPresent(): bool
    {
        return $this->clock_in_at !== null && $this->clock_out_at === null;
    }
}
