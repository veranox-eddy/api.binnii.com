<?php

namespace App\Models;

use App\Enums\CommentStatus;
use App\Enums\CommentThreadSubject;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['parent_id', 'media_id', 'journal_entry_id', 'child_id', 'guardian_id', 'thread_subject', 'body', 'status'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'thread_subject' => CommentThreadSubject::class,
            'status' => CommentStatus::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Threads belonging to a center via whichever relation is set
     * (child / media / guardian) — comments carry no center_id of their own.
     *
     * @param  Builder<Comment>  $query
     */
    public function scopeForCenter(Builder $query, int $centerId): void
    {
        $query->where(fn ($q) => $q
            ->whereHas('child', fn ($qq) => $qq->where('center_id', $centerId))
            ->orWhereHas('media', fn ($qq) => $qq->where('center_id', $centerId))
            ->orWhereHas('guardian', fn ($qq) => $qq->where('center_id', $centerId)));
    }

    /**
     * Everything a comment list row needs beyond its own columns: the author
     * plus the viewer-dependent like state (API_06).
     *
     * @param  Builder<Comment>  $query
     */
    public function scopeWithEngagement(Builder $query, Guardian $guardian): void
    {
        $query->with('guardian')
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('guardian_id', $guardian->getKey())]);
    }

    /** Guardian comments carry a guardian; replies without one are the center's. */
    public function authorName(): string
    {
        return $this->guardian?->fullName() ?? 'Center';
    }

    /**
     * The center this thread belongs to, derived from whichever relation is
     * set (media → child → guardian).
     */
    public function centerId(): ?int
    {
        return $this->media?->center_id ?? $this->child?->center_id ?? $this->guardian?->center_id;
    }
}
