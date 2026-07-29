<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Child;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    /** The parent-visible center calendar for one month or week (S17). */
    public function index(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'week' => ['nullable', 'date_format:Y-m-d', 'prohibits:month'],
        ]);

        $timezone = $child->center?->timezone ?? config('app.timezone');

        if ($request->filled('week')) {
            $anchor = Carbon::createFromFormat('Y-m-d', $request->string('week')->value(), $timezone);
            $range = ['week' => $anchor->toDateString()];
            [$start, $end] = [$anchor->copy()->startOfWeek(CarbonInterface::SUNDAY), $anchor->copy()->endOfWeek(CarbonInterface::SATURDAY)];
        } else {
            $anchor = $request->filled('month')
                ? Carbon::createFromFormat('Y-m', $request->string('month')->value(), $timezone)
                : ($child->center?->now() ?? now());
            $range = ['month' => $anchor->format('Y-m')];
            // The whole month grid, leading and trailing days included.
            [$start, $end] = [
                $anchor->copy()->startOfMonth()->startOfWeek(CarbonInterface::SUNDAY),
                $anchor->copy()->endOfMonth()->endOfWeek(CarbonInterface::SATURDAY),
            ];
        }

        $events = CalendarEvent::query()
            ->whereHas('calendar', fn ($q) => $q
                ->where('center_id', $child->center_id)
                ->where('parent_visible', true))
            // whereDate, not whereBetween: sqlite keeps a time-of-day on
            // date columns and would string-compare the upper bound away.
            ->whereDate('event_date', '>=', $start->toDateString())
            ->whereDate('event_date', '<=', $end->toDateString())
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'range' => $range,
            'child' => [
                'id' => $child->getKey(),
                'name' => $child->fullName(),
                'classroom' => $child->activeEnrollment()?->classroom?->name,
            ],
            'events' => $events->map(fn (CalendarEvent $event) => [
                'date' => $event->event_date->toDateString(),
                'title' => $event->title,
                'description' => $event->description,
                // The schema stores no files on calendar events; the flag
                // exists so the SPA contract holds if that ever changes.
                'has_attachment' => false,
            ]),
        ]);
    }
}
