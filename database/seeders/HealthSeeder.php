<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Child;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class HealthSeeder extends Seeder
{
    /**
     * Demo health logs (this week), sleep checks (today) and two incidents
     * with staff-present pivots.
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();
        $timezone = $center->timezone;
        $today = $center->now();
        $monday = $today->copy()->startOfWeek();

        $aaron = Child::where('center_id', $center->id)->where('first_name', 'Aaron')->firstOrFail();
        $mason = Child::where('center_id', $center->id)->where('first_name', 'Mason')->firstOrFail();
        $karson = Child::where('center_id', $center->id)->where('first_name', 'Karson Houlam')->firstOrFail();
        $joyce = Staff::where('center_id', $center->id)->where('last_name', 'Chang')->firstOrFail();
        $maria = Staff::where('center_id', $center->id)->where('last_name', 'Lopez')->firstOrFail();

        // Health logs across the week.
        foreach ([
            [$aaron, $monday->copy()->addDays(0)->setTime(9, 40), 'symptom', 'Fever', 'Sent home at noon.'],
            [$aaron, $monday->copy()->addDays(0)->setTime(9, 45), 'temperature', '38.4 °C', null],
            [$mason, $monday->copy()->addDays(1)->setTime(10, 15), 'symptom', 'Runny nose', null],
            [$mason, $monday->copy()->addDays(3)->setTime(14, 0), 'symptom', 'Cough', null],
            [$karson, $monday->copy()->addDays(2)->setTime(11, 30), 'medication', 'Ear drops', 'Per parent instructions.'],
        ] as [$child, $at, $type, $value, $notes]) {
            $classroomId = $child->enrollments()->where('status', 'active')->value('classroom_id');
            if ($child->healthLogs()->where('type', $type)->whereDate('logged_at', $at->toDateString())->doesntExist()) {
                $child->healthLogs()->create([
                    'classroom_id' => $classroomId,
                    'staff_id' => $joyce->id,
                    'logged_at' => Carbon::parse($at->format('Y-m-d H:i'), $timezone)->utc(),
                    'type' => $type,
                    'value' => $value,
                    'notes' => $notes,
                ]);
            }
        }

        // Periodic sleep checks for Karson today (every 30 minutes).
        $infantRoom = $karson->enrollments()->where('status', 'active')->value('classroom_id');
        foreach (['12:30', '13:00', '13:30'] as $i => $time) {
            $at = Carbon::parse($today->toDateString().' '.$time, $timezone)->utc();
            if ($karson->sleepChecks()->where('checked_at', $at)->doesntExist()) {
                $karson->sleepChecks()->create([
                    'classroom_id' => $infantRoom,
                    'staff_id' => $joyce->id,
                    'checked_at' => $at,
                    'position' => 'Back',
                    'status' => $i === 2 ? 'Awake' : 'Sleeping',
                ]);
            }
        }

        // Two incidents; one with parent notified + signature.
        foreach ([
            [$mason, 'Fall', $today->copy()->subDays(3)->setTime(10, 20), 'Tripped on the playground, small graze on knee. Cleaned and bandaged.', true, 'Sarah Cole', [$maria]],
            [$aaron, 'Bump', $today->copy()->subDays(10)->setTime(15, 5), 'Bumped heads with a friend during play. Ice applied.', false, null, [$joyce, $maria]],
        ] as [$child, $type, $at, $description, $notified, $signature, $staffPresent]) {
            if ($child->incidents()->where('type_of_incident', $type)->exists()) {
                continue;
            }

            $incident = $child->incidents()->create([
                'classroom_id' => $child->enrollments()->where('status', 'active')->value('classroom_id'),
                'type_of_incident' => $type,
                'occurred_at' => Carbon::parse($at->format('Y-m-d H:i'), $timezone)->utc(),
                'description' => $description,
                'parent_notified' => $notified,
                'parent_notified_at' => $notified ? Carbon::parse($at->format('Y-m-d H:i'), $timezone)->addMinutes(40)->utc() : null,
                'parent_signature' => $signature,
                'reported_by' => $staffPresent[0]->id,
            ]);
            $incident->staffPresent()->sync(collect($staffPresent)->pluck('id'));
        }
    }
}
