<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Enums\MediaStatus;
use App\Enums\ReportStatus;
use App\Models\Child;
use App\Models\Entry;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\Media;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The merged child feed (S07/S03): released center entries, released center
 * photos, and the family's own journal entries, newest day first.
 *
 * The three live in unrelated tables, so pagination runs over a UNION of
 * (kind, id, sort_date, sort_at) and the rows are hydrated afterwards.
 * Paginating each source separately and merging in PHP would need every row
 * of every source in memory before it could answer page one.
 */
class JournalFeed
{
    /**
     * Entry types the center's media delay covers. `delayed_media_hours`
     * exists so parents do not watch the day unfold minute by minute, which
     * is about naps, meals and photos — a check-in or an incident is not
     * something to sit on.
     */
    private const array DELAYED_ENTRY_TYPES = [EntryType::Sleep, EntryType::Food];

    private readonly int $delayHours;

    private readonly Carbon $now;

    public function __construct(
        private readonly Guardian $guardian,
        private readonly Child $child,
    ) {
        $settings = $this->child->center?->settings;

        $this->delayHours = (int) ($settings->delayed_media_hours ?? 0);
        $this->now = $this->child->center?->now() ?? now();
    }

    public function paginate(int $perPage = 20): CursorPaginator
    {
        $union = $this->journalKeys()
            ->unionAll($this->entryKeys())
            ->unionAll($this->mediaKeys());

        $page = DB::query()
            ->fromSub($union, 'feed')
            ->orderByDesc('sort_date')
            ->orderByDesc('sort_at')
            ->orderBy('kind')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $rows = collect($page->items());

        $journal = $this->hydrateJournal($rows);
        $entries = $this->hydrateEntries($rows);
        $media = $this->hydrateMedia($rows);

        return $page->through(fn (object $row) => match ($row->kind) {
            'journal_entry' => $this->shapeJournal($journal[$row->id]),
            'entry' => $this->shapeEntry($entries[$row->id]),
            'media' => $this->shapeMedia($media[$row->id]),
        });
    }

    /** The guardian's own journal entries they are allowed to see. */
    private function journalKeys(): QueryBuilder
    {
        $visible = JournalEntry::query()->visibleTo($this->guardian)->where('child_id', $this->child->getKey());

        return DB::query()
            ->selectRaw("'journal_entry' as kind, id, entry_date as sort_date, created_at as sort_at")
            ->fromSub($visible->toBase(), 'j');
    }

    /** Center care-log entries, but only once the day's report was sent. */
    private function entryKeys(): QueryBuilder
    {
        $query = Entry::query()
            ->where('entries.child_id', $this->child->getKey())
            ->whereExists(fn (QueryBuilder $q) => $q->from('daily_reports')
                ->whereColumn('daily_reports.child_id', 'entries.child_id')
                ->whereRaw('date(daily_reports.report_date) = date(entries.occurred_at)')
                ->where('daily_reports.status', ReportStatus::Sent->value));

        $this->applyDelay($query, 'entries.occurred_at', array_map(
            fn (EntryType $type) => $type->value,
            self::DELAYED_ENTRY_TYPES,
        ));

        return DB::query()
            ->selectRaw("'entry' as kind, id, date(occurred_at) as sort_date, occurred_at as sort_at")
            ->fromSub($query->toBase(), 'e');
    }

    /** Center photos tagged to this child and released to families. */
    private function mediaKeys(): QueryBuilder
    {
        $query = Media::query()
            ->where('media.status', MediaStatus::Sent->value)
            ->whereExists(fn (QueryBuilder $q) => $q->from('media_child')
                ->whereColumn('media_child.media_id', 'media.id')
                ->where('media_child.child_id', $this->child->getKey()))
            // Center-wide media carries no classroom; only a classroom that
            // exists can have switched photo sharing off.
            ->where(fn ($q) => $q->whereNull('media.classroom_id')
                ->orWhereExists(fn (QueryBuilder $qq) => $qq->from('classrooms')
                    ->whereColumn('classrooms.id', 'media.classroom_id')
                    ->where('classrooms.photo_sharing_enabled', true)));

        if (! $this->hasFullPhotoAccess()) {
            $query->whereNotExists(fn (QueryBuilder $q) => $q->from('media_child')
                ->whereColumn('media_child.media_id', 'media.id')
                ->where('media_child.child_id', '!=', $this->child->getKey()));
        }

        $this->applyDelay($query, 'media.occurred_at');

        return DB::query()
            ->selectRaw('\'media\' as kind, id, date(coalesce(occurred_at, sent_at, created_at)) as sort_date, coalesce(occurred_at, sent_at, created_at) as sort_at')
            ->fromSub($query->toBase(), 'm');
    }

    /**
     * Hide anything that happened within the center's delay window. With
     * `$types` the delay only covers those entry types; without it, the
     * whole source is delayed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @param  array<int, string>  $types
     */
    private function applyDelay($query, string $column, array $types = []): void
    {
        if ($this->delayHours === 0) {
            return;
        }

        $cutoff = $this->now->copy()->subHours($this->delayHours);

        $query->where(function ($q) use ($column, $cutoff, $types) {
            $q->where($column, '<=', $cutoff)->orWhereNull($column);

            if ($types !== []) {
                $q->orWhereNotIn('type', $types);
            }
        });
    }

    private function hasFullPhotoAccess(): bool
    {
        return (bool) ($this->guardian->accessTo($this->child->getKey())['has_full_photo_access'] ?? false);
    }

    /** @return Collection<int, JournalEntry> */
    private function hydrateJournal(Collection $rows): Collection
    {
        return JournalEntry::with(['media', 'guardian'])
            ->withCount(['comments', 'likes'])
            ->withExists($this->myLike())
            ->whereKey($this->idsOf($rows, 'journal_entry'))
            ->get()
            ->keyBy('id');
    }

    /** @return Collection<int, Entry> */
    private function hydrateEntries(Collection $rows): Collection
    {
        return Entry::with('staff')->whereKey($this->idsOf($rows, 'entry'))->get()->keyBy('id');
    }

    /** @return Collection<int, Media> */
    private function hydrateMedia(Collection $rows): Collection
    {
        return Media::withCount(['comments', 'likes'])
            ->withExists($this->myLike())
            ->with('uploader')
            ->whereKey($this->idsOf($rows, 'media'))
            ->get()
            ->keyBy('id');
    }

    /** @return array<string, \Closure> */
    private function myLike(): array
    {
        return ['likes as liked_by_me' => fn ($q) => $q->where('guardian_id', $this->guardian->getKey())];
    }

    /** @return array<int, int> */
    private function idsOf(Collection $rows, string $kind): array
    {
        return $rows->where('kind', $kind)->pluck('id')->all();
    }

    /** @return array<string, mixed> */
    private function shapeJournal(JournalEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'kind' => 'journal_entry',
            'date' => $entry->entry_date->toDateString(),
            'occurred_at' => $entry->created_at?->toIso8601String(),
            'title' => $entry->title,
            'summary' => $entry->description,
            'author' => $this->author('guardian', $entry->guardian, $entry->guardian_id === $this->guardian->getKey()),
            'is_private' => $entry->is_private,
            'is_favorite' => $entry->is_favorite,
            'is_milestone' => $entry->is_milestone,
            'media' => $entry->media->map(fn ($item) => [
                'id' => $item->id,
                'type' => $item->media_type->value,
                'url' => $item->url,
                'thumb_url' => $item->url,
            ])->all(),
            'comment_count' => $entry->comments_count,
            'likes_count' => $entry->likes_count,
            'liked_by_me' => (bool) $entry->liked_by_me,
        ];
    }

    /** @return array<string, mixed> */
    private function shapeEntry(Entry $entry): array
    {
        return [
            'id' => $entry->id,
            'kind' => 'entry',
            'date' => $entry->occurred_at->toDateString(),
            'occurred_at' => $entry->occurred_at->toIso8601String(),
            'title' => $entry->type->label(),
            'summary' => $entry->summary() ?: null,
            'author' => $this->author('staff', $entry->staff),
            'is_private' => false,
            'is_favorite' => false,
            'is_milestone' => false,
            'media' => [],
            'comment_count' => 0,
            'likes_count' => 0,
            'liked_by_me' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function shapeMedia(Media $media): array
    {
        $at = $media->occurred_at ?? $media->sent_at ?? $media->created_at;

        return [
            'id' => $media->id,
            'kind' => 'media',
            'date' => $at?->toDateString(),
            'occurred_at' => $at?->toIso8601String(),
            'title' => $media->caption,
            'summary' => null,
            'author' => $this->author('staff', $media->uploader),
            'is_private' => false,
            'is_favorite' => false,
            'is_milestone' => false,
            'media' => [[
                'id' => $media->id,
                'type' => $media->media_type->value,
                'url' => route('api.media.download', $media),
                'thumb_url' => route('api.media.download', $media),
            ]],
            'comment_count' => $media->comments_count,
            'likes_count' => $media->likes_count,
            'liked_by_me' => (bool) $media->liked_by_me,
        ];
    }

    /** @return array{type: string, name: string|null} */
    private function author(string $type, ?object $model, bool $isViewer = false): array
    {
        if ($isViewer) {
            return ['type' => $type, 'name' => 'Me'];
        }

        return [
            'type' => $type,
            'name' => $model?->fullName() ?? ($type === 'staff' ? 'Center' : null),
        ];
    }
}
