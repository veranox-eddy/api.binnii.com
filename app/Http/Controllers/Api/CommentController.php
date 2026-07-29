<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommentStatus;
use App\Enums\CommentThreadSubject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Guardian;
use App\Models\JournalEntry;
use App\Models\Like;
use App\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function forMedia(Media $media): AnonymousResourceCollection
    {
        $this->authorize('view', $media);

        return CommentResource::collection($this->thread($media->comments()->getQuery()));
    }

    public function forJournalEntry(JournalEntry $journalEntry): AnonymousResourceCollection
    {
        $this->authorize('view', $journalEntry);

        return CommentResource::collection($this->thread($journalEntry->comments()->getQuery()));
    }

    public function store(StoreCommentRequest $request): CommentResource
    {
        $guardian = $this->guardian();
        $target = $this->target($request);

        $this->authorize('view', $target);
        $this->assertAboutChild($request, $target);
        $this->assertParentOnSameTarget($request);

        $comment = Comment::create([
            'parent_id' => $request->input('parent_id'),
            'media_id' => $target instanceof Media ? $target->getKey() : null,
            'journal_entry_id' => $target instanceof JournalEntry ? $target->getKey() : null,
            'child_id' => $request->integer('child_id'),
            'guardian_id' => $guardian->getKey(),
            'thread_subject' => CommentThreadSubject::Post,
            'body' => $request->string('body')->value(),
            'status' => CommentStatus::Inbox,
        ]);

        return new CommentResource($comment->load('guardian'));
    }

    public function destroy(Comment $comment): Response
    {
        $this->authorize('delete', $comment);

        DB::transaction(function () use ($comment) {
            $thread = Comment::whereKey($comment->getKey())->orWhere('parent_id', $comment->getKey());

            // Replies (the center's included) go with the thread they hang
            // off; their likes are morph rows with no FK, so by hand.
            Like::where('likeable_type', 'comment')->whereIn('likeable_id', $thread->clone()->select('id'))->delete();
            $comment->replies()->delete();
            $comment->delete();
        });

        return response()->noContent();
    }

    /**
     * Root comments with their replies, oldest first, each row carrying the
     * viewer's like state.
     *
     * @param  Builder<Comment>  $query
     * @return Collection<int, Comment>
     */
    private function thread(Builder $query)
    {
        $guardian = $this->guardian();

        return $query->whereNull('parent_id')
            ->withEngagement($guardian)
            ->with(['replies' => fn ($q) => $q->withEngagement($guardian)])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    private function target(StoreCommentRequest $request): Media|JournalEntry
    {
        return $request->filled('media_id')
            ? Media::findOrFail($request->integer('media_id'))
            : JournalEntry::findOrFail($request->integer('journal_entry_id'));
    }

    /** The pivot row named in `child_id` must be the child the target is about. */
    private function assertAboutChild(StoreCommentRequest $request, Media|JournalEntry $target): void
    {
        $childId = $request->integer('child_id');

        if (! $this->guardian()->ownsChild($childId)) {
            throw ValidationException::withMessages(['child_id' => 'This child is not on your account.']);
        }

        $matches = $target instanceof Media
            ? $target->children()->whereKey($childId)->exists()
            : $target->child_id === $childId;

        if (! $matches) {
            throw ValidationException::withMessages(['child_id' => 'This child is not part of what you are commenting on.']);
        }
    }

    /** A reply may only thread under a comment on the same photo/entry. */
    private function assertParentOnSameTarget(StoreCommentRequest $request): void
    {
        if (! $request->filled('parent_id')) {
            return;
        }

        $parent = Comment::findOrFail($request->integer('parent_id'));

        $sameTarget = $request->filled('media_id')
            ? $parent->media_id === $request->integer('media_id')
            : $parent->journal_entry_id === $request->integer('journal_entry_id');

        if (! $sameTarget || $parent->parent_id !== null) {
            throw ValidationException::withMessages(['parent_id' => 'Replies must be to a top-level comment on the same item.']);
        }
    }

    private function guardian(): Guardian
    {
        return auth('guardian')->user();
    }
}
