<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\ChildAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'child_id', 'classroom_id', 'attendance_date', 'check_in_at', 'check_in_by',
    'check_out_at', 'check_out_by', 'check_in_signature', 'check_out_signature',
    'status', 'moved_to_classroom_id', 'moved_to_virtual_area_id',
])]
class ChildAttendance extends Model
{
    /** @use HasFactory<ChildAttendanceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'status' => AttendanceStatus::class,
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

    public function movedToClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'moved_to_classroom_id');
    }

    public function movedToVirtualArea(): BelongsTo
    {
        return $this->belongsTo(VirtualArea::class, 'moved_to_virtual_area_id');
    }

    /**
     * The classroom the child is in right now: the move destination when the
     * child was moved to another room, otherwise the check-in room. Moves to
     * a virtual area (Gym, Playground) stay with the origin room — the room
     * remains responsible for the child.
     */
    public function currentClassroomId(): ?int
    {
        return $this->moved_to_classroom_id ?? $this->classroom_id;
    }

    /**
     * Check a child in for the given date (one row per child per day). A
     * re-check-in after a check-out reuses the row and must clear the stale
     * check-out fields, or grids would show a checkout before the check-in.
     */
    public static function checkIn(Child $child, int $classroomId, string $date, string $by, ?string $signature = null): self
    {
        // whereDate, not attribute equality: date casts store a time component
        // on some drivers, so firstOrNew would silently create duplicates.
        $attendance = self::where('child_id', $child->id)->whereDate('attendance_date', $date)->first()
            ?? new self(['child_id' => $child->id, 'attendance_date' => $date]);

        $attendance->fill([
            'classroom_id' => $attendance->classroom_id ?? $classroomId,
            'check_in_at' => now(),
            'check_in_by' => $by,
            'check_in_signature' => $signature ?? $attendance->check_in_signature,
            'check_out_at' => null,
            'check_out_by' => null,
            'check_out_signature' => null,
            'status' => AttendanceStatus::Present,
        ])->save();

        return $attendance;
    }

    public function checkOut(string $by, ?string $signature = null): void
    {
        $this->update([
            'check_out_at' => now(),
            'check_out_by' => $by,
            'check_out_signature' => $signature ?? $this->check_out_signature,
            'status' => AttendanceStatus::CheckedOut,
        ]);
    }

    /**
     * MOVE TO another classroom or a virtual area (exactly one of the two).
     */
    public function moveTo(?int $classroomId, ?int $virtualAreaId): void
    {
        $this->update([
            'status' => AttendanceStatus::Moved,
            'moved_to_classroom_id' => $classroomId,
            'moved_to_virtual_area_id' => $classroomId ? null : $virtualAreaId,
        ]);
    }

    /** Duration like the grids show it: "08h16m". */
    public function duration(): ?string
    {
        if (! $this->check_in_at || ! $this->check_out_at || $this->check_out_at->lt($this->check_in_at)) {
            return null;
        }

        $minutes = (int) $this->check_in_at->diffInMinutes($this->check_out_at);

        return sprintf('%02dh%02dm', intdiv($minutes, 60), $minutes % 60);
    }
}
