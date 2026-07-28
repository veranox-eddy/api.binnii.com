<?php

namespace Database\Seeders;

use App\Enums\CurriculumLevel;
use App\Enums\MenusCalendarType;
use App\Models\Center;
use App\Models\Classroom;
use App\Models\CurriculumAssignment;
use App\Models\WeeklyPlan;
use App\Models\WeeklyRoutine;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Demo activity library, a weekly plan for the Three to Five Room,
     * curriculum assignments and a center calendar + menu.
     */
    public function run(): void
    {
        $center = Center::where('name', 'Childcare Centre Inc.')->firstOrFail();
        $monday = $center->now()->startOfWeek();

        // Activity Library.
        $activities = collect([
            ['Mosaic Melting Snowman Craft', 'Children use white paper squares to create a mosaic melting snowman.', 'Art, Sensory'],
            ['Story Circle', 'Group reading with picture prompts and follow-up questions.', 'Literacy, Language, Social'],
            ['Counting Bears Sort', 'Sort and count coloured bears into matching bowls.', 'Math, Play'],
            ['Nature Walk Scavenger Hunt', 'Outdoor walk collecting leaves, rocks and other treasures.', 'Outdoor, Sensory'],
            ['Rhythm Sticks Sing-Along', 'Keep the beat with rhythm sticks during circle-time songs.', 'Music, Social'],
        ])->mapWithKeys(fn (array $row) => [
            $row[0] => $center->activities()->firstOrCreate(['title' => $row[0]], [
                'description' => $row[1],
                'tags' => $row[2],
            ]),
        ]);

        // The wireframe's six routine rows, per teaching classroom.
        $rooms = Classroom::where('center_id', $center->id)->where('is_floating', false)->get();
        foreach ($rooms as $room) {
            foreach (WeeklyRoutine::DEFAULTS as $i => [$name, $color]) {
                WeeklyRoutine::firstOrCreate(
                    ['classroom_id' => $room->id, 'name' => $name],
                    ['color' => $color, 'sort_order' => $i],
                );
            }
        }

        // Weekly plan for the Three to Five Room (this week).
        $preschoolRoom = Classroom::where('center_id', $center->id)->where('name', 'Three to Five Room')->firstOrFail();
        $plan = WeeklyPlan::forWeek($preschoolRoom->id, $monday->toDateString());

        $routineId = fn (string $name) => WeeklyRoutine::where('classroom_id', $preschoolRoom->id)
            ->where('name', $name)->value('id');

        foreach ([
            [0, 'Story Circle', null, 'Read-Aloud'],
            [0, null, 'Welcome songs and weather chart.', 'Circle Time'],
            [2, 'Counting Bears Sort', 'Small groups of four.', 'Small Group'],
            [4, 'Nature Walk Scavenger Hunt', 'Weather permitting.', 'Outdoor Play'],
        ] as [$offset, $activityTitle, $notes, $routineName]) {
            $date = $monday->copy()->addDays($offset)->toDateString();
            $activityId = $activityTitle ? $activities[$activityTitle]->id : null;
            $rid = $routineId($routineName);

            $exists = $plan->items()->whereDate('plan_date', $date)
                ->where('weekly_routine_id', $rid)
                ->where('activity_id', $activityId)
                ->where('notes', $notes)
                ->exists();
            if (! $exists) {
                $plan->items()->create([
                    'weekly_routine_id' => $rid,
                    'plan_date' => $date,
                    'activity_id' => $activityId,
                    'notes' => $notes,
                    'sort_order' => $plan->items()->whereDate('plan_date', $date)->count() + 1,
                ]);
            }
        }

        // Curriculum assignments.
        foreach ([
            'Infant and Toddler Room' => CurriculumLevel::Infants,
            'Three to Five Room' => CurriculumLevel::Preschool,
        ] as $roomName => $level) {
            $classroom = Classroom::where('center_id', $center->id)->where('name', $roomName)->first();
            if ($classroom) {
                CurriculumAssignment::updateOrCreate(
                    ['classroom_id' => $classroom->id],
                    ['curriculum' => $level],
                );
            }
        }

        // Center calendar with events + a parent-visible menu.
        $calendar = $center->menusCalendars()->firstOrCreate(
            ['name' => 'Center Calendar'],
            ['type' => MenusCalendarType::Calendar, 'parent_visible' => true],
        );

        foreach ([
            [$monday->copy()->addDays(4), 'Pizza Day', 'Whole-center pizza lunch.'],
            [$monday->copy()->addDays(9), 'Field Trip', 'Preschool visit to the public library.'],
        ] as [$date, $title, $description]) {
            if ($calendar->events()->where('title', $title)->whereDate('event_date', $date->toDateString())->doesntExist()) {
                $calendar->events()->create([
                    'event_date' => $date->toDateString(),
                    'title' => $title,
                    'description' => $description,
                ]);
            }
        }

        $center->menusCalendars()->firstOrCreate(
            ['name' => 'Weekly Lunch Menu'],
            ['type' => MenusCalendarType::Menu, 'parent_visible' => true],
        );
    }
}
