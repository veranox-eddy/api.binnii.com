<?php

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Enums\EntryType;
use App\Models\Center;
use App\Models\DailyReport;
use App\Models\Entry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EntrySeeder extends Seeder
{
    /**
     * Demo entries + daily reports: today's entries (reports open) for the
     * Three to Five Room kids and yesterday's sent reports for the same
     * children, so both states appear in the report loop.
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();
        $timezone = $center->timezone;
        $today = $center->now();
        $yesterday = $today->copy()->subWeekday();

        $children = $center->children()
            ->whereHas('enrollments', fn ($q) => $q->where('status', EnrollmentStatus::Active))
            ->with('enrollments')
            ->orderBy('id')
            ->take(5)
            ->get();

        foreach ($children as $child) {
            $classroomId = $child->enrollments->firstWhere('status', EnrollmentStatus::Active)->classroom_id;

            foreach ([$yesterday, $today] as $day) {
                if ($child->entries()->whereDate('occurred_at', $day->toDateString())->exists()) {
                    continue;
                }

                $at = fn (string $time) => Carbon::parse($day->toDateString().' '.$time, $timezone)->utc();
                $rows = [
                    [EntryType::Food, '08:45', ['meal' => 'Breakfast', 'amount' => 'Most', 'notes' => 'Oatmeal and fruit']],
                    [EntryType::Sleep, '12:30', ['start_time' => '12:30', 'end_time' => '14:05', 'notes' => null]],
                    [EntryType::Mood, '10:15', ['mood' => 'Happy', 'level' => 'Very', 'notes' => null]],
                    [EntryType::Activity, '10:40', ['notes' => 'Outdoor play and story time']],
                    [EntryType::Temperature, '09:05', ['value' => '36.8 °C', 'notes' => null]],
                ];

                foreach ($rows as [$type, $time, $payload]) {
                    Entry::create([
                        'child_id' => $child->id,
                        'classroom_id' => $classroomId,
                        'type' => $type,
                        'occurred_at' => $at($time),
                        'payload' => array_filter($payload, fn ($v) => $v !== null),
                    ]);
                }

                $report = DailyReport::ensureFor($child, $day->toDateString());

                if ($day->isSameDay($yesterday)) {
                    $report->send();
                }
            }
        }
    }
}
