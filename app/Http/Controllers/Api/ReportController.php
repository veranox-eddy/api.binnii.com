<?php

namespace App\Http\Controllers\Api;

use App\Enums\EntryType;
use App\Enums\MediaStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\DailyReport;
use App\Models\Entry;
use App\Models\Guardian;
use App\Models\Media;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /**
     * The parent-facing section order (API_12). move_rooms and
     * name_to_face are staff bookkeeping and stay off the report.
     */
    private const array SECTION_ORDER = [
        EntryType::CheckIn, EntryType::Food, EntryType::Fluids, EntryType::Sleep,
        EntryType::Toilet, EntryType::Mood, EntryType::Activity, EntryType::Health,
        EntryType::Temperature, EntryType::Incident, EntryType::Supplies,
        EntryType::Notes, EntryType::CheckOut,
    ];

    /** The types the center's media delay covers — same set as the feed. */
    private const array DELAYED_ENTRY_TYPES = [EntryType::Sleep, EntryType::Food];

    /** One child's day (S19), released only once the center sent it. */
    public function show(Request $request, Child $child): JsonResponse
    {
        $this->authorize('view', $child);

        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $timezone = $child->center?->timezone ?? config('app.timezone');
        $date = $request->input('date') ?: ($child->center?->now() ?? now())->toDateString();

        $report = DailyReport::where('child_id', $child->getKey())
            ->whereDate('report_date', $date)
            ->first();

        // An open report reads exactly like a missing one apart from the
        // flag — its entries are the center's drafts, not the family's yet.
        if ($report === null || $report->status !== ReportStatus::Sent) {
            return response()->json([
                'status' => $report === null ? 'none' : 'not_finalized',
                'date' => $date,
                'sections' => [],
                'media' => [],
            ]);
        }

        $cutoff = $this->delayCutoff($child);

        return response()->json([
            'status' => 'sent',
            'date' => $date,
            'sent_at' => $report->sent_at?->setTimezone($timezone)->toIso8601String(),
            'sections' => $this->sections($report, $timezone, $cutoff),
            'media' => $this->media($child, $date, $cutoff),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(DailyReport $report, string $timezone, ?Carbon $cutoff): array
    {
        $entries = $report->entriesQuery()->get()
            ->reject(fn (Entry $entry) => $cutoff !== null
                && in_array($entry->type, self::DELAYED_ENTRY_TYPES, strict: true)
                && $entry->occurred_at->gt($cutoff))
            ->groupBy(fn (Entry $entry) => $entry->type->value);

        return collect(self::SECTION_ORDER)
            ->filter(fn (EntryType $type) => $entries->has($type->value))
            ->map(fn (EntryType $type) => [
                'type' => $type->value,
                'label' => $type->label(),
                'items' => $entries[$type->value]->map(fn (Entry $entry) => [
                    'time' => $entry->occurred_at->setTimezone($timezone)->format('H:i'),
                    'summary' => $entry->summary(),
                    'qty' => $entry->qty(),
                ])->values(),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function media(Child $child, string $date, ?Carbon $cutoff): array
    {
        $query = Media::query()
            ->where('media.status', MediaStatus::Sent->value)
            ->whereExists(fn (QueryBuilder $q) => $q->from('media_child')
                ->whereColumn('media_child.media_id', 'media.id')
                ->where('media_child.child_id', $child->getKey()))
            ->whereDate('media.occurred_at', $date)
            // Same visibility rules as the feed: sharing on (or no
            // classroom), and group photos only with full photo access.
            ->where(fn ($q) => $q->whereNull('media.classroom_id')
                ->orWhereExists(fn (QueryBuilder $qq) => $qq->from('classrooms')
                    ->whereColumn('classrooms.id', 'media.classroom_id')
                    ->where('classrooms.photo_sharing_enabled', true)));

        if (! $this->hasFullPhotoAccess($child)) {
            $query->whereNotExists(fn (QueryBuilder $q) => $q->from('media_child')
                ->whereColumn('media_child.media_id', 'media.id')
                ->where('media_child.child_id', '!=', $child->getKey()));
        }

        if ($cutoff !== null) {
            $query->where(fn ($q) => $q->where('media.occurred_at', '<=', $cutoff)->orWhereNull('media.occurred_at'));
        }

        return $query->orderBy('media.occurred_at')->get()
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'type' => $media->media_type->value,
                'url' => route('api.media.download', $media),
                'thumb_url' => route('api.media.download', $media),
                'occurred_at' => $media->occurred_at?->toIso8601String(),
            ])
            ->all();
    }

    private function delayCutoff(Child $child): ?Carbon
    {
        $hours = (int) ($child->center?->settings?->delayed_media_hours ?? 0);

        if ($hours === 0) {
            return null;
        }

        return ($child->center?->now() ?? now())->copy()->subHours($hours)->utc();
    }

    private function hasFullPhotoAccess(Child $child): bool
    {
        return (bool) ($this->guardian()->accessTo($child->getKey())['has_full_photo_access'] ?? false);
    }

    private function guardian(): Guardian
    {
        return auth('guardian')->user();
    }
}
