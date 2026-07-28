<?php

namespace Database\Seeders;

use App\Enums\AbsenceReason;
use App\Enums\AttendanceStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\StaffAttendanceSource;
use App\Models\Center;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * A demo day of attendance (today, center timezone) plus the past week,
     * so the roll-call, weekly grid and monthly view all render with data.
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();
        $timezone = $center->timezone;
        $today = $center->now();

        foreach ([['Playground', 1], ['Gym', 2]] as [$name, $order]) {
            $center->virtualAreas()->firstOrCreate(['name' => $name], ['sort_order' => $order]);
        }

        $children = $center->children()
            ->whereHas('enrollments', fn ($q) => $q->where('status', EnrollmentStatus::Active))
            ->with('enrollments')
            ->orderBy('id')
            ->get();

        // Two children are absent today (the roll-call shows them with Edit).
        // Date-keyed lookups use whereDate: date casts store a time component
        // on sqlite, so firstOrCreate on the raw string would duplicate rows.
        $absentToday = $children->take(2);
        foreach ($absentToday->values() as $i => $child) {
            if ($child->absences()->whereDate('start_date', $today->toDateString())->doesntExist()) {
                $child->absences()->create([
                    'start_date' => $today->toDateString(),
                    'reason' => $i === 0 ? AbsenceReason::HomeDay : AbsenceReason::Sick,
                ]);
            }
        }

        // Past 5 weekdays + today: check-ins around 9am, check-outs around 5pm.
        $days = collect(range(5, 0))->map(fn (int $back) => $today->copy()->subWeekdays($back));

        foreach ($children as $child) {
            $enrollment = $child->enrollments->firstWhere('status', EnrollmentStatus::Active);

            foreach ($days as $day) {
                if ($absentToday->contains($child) && $day->isSameDay($today)) {
                    continue;
                }

                if ($child->attendances()->whereDate('attendance_date', $day->toDateString())->exists()) {
                    continue;
                }

                $in = Carbon::parse($day->toDateString().' 09:00', $timezone)->addMinutes($child->id % 45)->utc();
                $isToday = $day->isSameDay($today);

                $child->attendances()->create([
                    'attendance_date' => $day->toDateString(),
                    'classroom_id' => $enrollment->classroom_id,
                    'check_in_at' => $in,
                    'check_in_by' => 'Kiosk',
                    'check_out_at' => $isToday ? null : $in->copy()->addHours(8)->addMinutes($child->id % 20),
                    'check_out_by' => $isToday ? null : 'Kiosk',
                    'status' => $isToday ? AttendanceStatus::Present : AttendanceStatus::CheckedOut,
                ]);
            }
        }

        // Staff: Joyce and Maria are clocked in today (and worked the past
        // week, so the Time Log / Time Cards pages have data); Anna is off sick.
        foreach (['Chang', 'Lopez'] as $lastName) {
            $staff = Staff::where('center_id', $center->id)->where('last_name', $lastName)->firstOrFail();

            foreach ($days as $day) {
                if ($staff->attendances()->whereDate('work_date', $day->toDateString())->exists()) {
                    continue;
                }

                $isToday = $day->isSameDay($today);
                $in = Carbon::parse($day->toDateString().' 08:30', $timezone)->addMinutes($staff->id % 15)->utc();

                $staff->attendances()->create([
                    'work_date' => $day->toDateString(),
                    'clock_in_at' => $in,
                    'clock_out_at' => $isToday ? null : $in->copy()->addHours(8),
                    'source' => StaffAttendanceSource::Kiosk,
                ]);
            }
        }

        $anna = Staff::where('center_id', $center->id)->where('last_name', 'Kim')->firstOrFail();
        if ($anna->absences()->whereDate('start_date', $today->toDateString())->doesntExist()) {
            $anna->absences()->create(['start_date' => $today->toDateString(), 'reason' => AbsenceReason::Sick]);
        }
    }
}
