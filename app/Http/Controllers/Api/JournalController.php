<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ShareJournalEntryRequest;
use App\Http\Requests\Api\StoreJournalEntryRequest;
use App\Http\Requests\Api\UpdateJournalEntryRequest;
use App\Http\Resources\FeedItemResource;
use App\Http\Resources\JournalEntryListItemResource;
use App\Http\Resources\JournalEntryResource;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\Like;
use App\Services\JournalFeed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JournalController extends Controller
{
    /** The merged feed: released center entries + photos + family journal. */
    public function feed(Request $request, Child $child): AnonymousResourceCollection
    {
        $this->authorize('view', $child);

        $feed = new JournalFeed($this->guardian(), $child->load('center.settings'));

        return FeedItemResource::collection(
            $feed->paginate((int) $request->integer('per_page', 20)),
        );
    }

    /** The tabular list (S09) — guardian journal entries only. */
    public function entries(Child $child): AnonymousResourceCollection
    {
        $this->authorize('view', $child);

        return JournalEntryListItemResource::collection(
            JournalEntry::query()
                ->visibleTo($this->guardian())
                ->where('child_id', $child->getKey())
                ->with('guardian')
                ->withCount('media')
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->paginate(50),
        );
    }

    public function store(StoreJournalEntryRequest $request, Child $child): JournalEntryResource
    {
        $this->authorize('view', $child);

        $guardian = $this->guardian();

        $entry = DB::transaction(function () use ($request, $child, $guardian) {
            $entry = JournalEntry::create([
                'child_id' => $child->getKey(),
                'guardian_id' => $guardian->getKey(),
                'title' => $request->string('title')->value(),
                'description' => $request->input('description'),
                'entry_date' => $request->input('entry_date') ?: $this->today($child),
                'is_private' => $request->boolean('is_private'),
                'is_favorite' => $request->boolean('is_favorite'),
                'is_milestone' => $request->boolean('is_milestone'),
            ]);

            foreach (array_values($request->file('media')) as $index => $file) {
                $entry->media()->create([
                    'media_type' => $this->mediaTypeOf($file),
                    'file_path' => $file->store("journal/{$child->getKey()}", 'public'),
                    'sort_order' => $index,
                ]);
            }

            return $entry;
        });

        return $this->present($entry);
    }

    public function show(JournalEntry $journalEntry): JournalEntryResource
    {
        $this->authorize('view', $journalEntry);

        return $this->present($journalEntry);
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): JournalEntryResource
    {
        $this->authorize('update', $journalEntry);

        $journalEntry->update($request->only([
            'title', 'description', 'entry_date', 'is_private', 'is_favorite', 'is_milestone',
        ]));

        return $this->present($journalEntry);
    }

    public function destroy(JournalEntry $journalEntry): Response
    {
        $this->authorize('delete', $journalEntry);

        // The media rows cascade with the entry; their files do not, so
        // remove them here or the disk keeps growing forever.
        $paths = $journalEntry->media->pluck('file_path')->all();

        DB::transaction(function () use ($journalEntry) {
            // Comments and likes hold plain FKs / morph rows, not cascades:
            // replies before parents, likes before their comments.
            $comments = $journalEntry->comments();

            Like::where('likeable_type', 'comment')->whereIn('likeable_id', $comments->select('id'))->delete();
            $comments->clone()->whereNotNull('parent_id')->delete();
            $comments->clone()->delete();
            $journalEntry->likes()->delete();

            $journalEntry->delete();
        });

        Storage::disk('public')->delete($paths);

        return response()->noContent();
    }

    /** Share with the Crew (or stop sharing) — the inverse of `is_private`. */
    public function share(ShareJournalEntryRequest $request, JournalEntry $journalEntry): JournalEntryResource
    {
        $this->authorize('share', $journalEntry);

        $journalEntry->update([
            'is_private' => $request->has('shared')
                ? ! $request->boolean('shared')
                : ! $journalEntry->is_private,
        ]);

        return $this->present($journalEntry);
    }

    /** One entry, ready for the wire: relations plus the viewer's like state. */
    private function present(JournalEntry $entry): JournalEntryResource
    {
        return new JournalEntryResource(
            $entry->load('media', 'guardian')
                ->loadCount(['comments', 'likes'])
                ->loadExists(['likes as liked_by_me' => fn ($q) => $q->where('guardian_id', $this->guardian()->getKey())]),
        );
    }

    /** "Blank in the UI means today" — the center's today, not the server's. */
    private function today(Child $child): string
    {
        return ($child->center?->now() ?? now())->toDateString();
    }

    private function mediaTypeOf(UploadedFile $file): MediaType
    {
        return str_starts_with((string) $file->getMimeType(), 'video/')
            ? MediaType::Video
            : MediaType::Photo;
    }

    private function guardian(): Guardian
    {
        return auth('guardian')->user();
    }
}
