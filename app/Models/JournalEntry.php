<?php

namespace App\Models;

use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/*
 * A guardian's own journal entry about their child. Never surfaced to the
 * center — the staff-authored counterpart is Entry/Media, and nothing here
 * carries a center_id or staff_id for an admin query to find.
 */
#[Fillable([
    'child_id', 'guardian_id', 'title', 'description', 'entry_date',
    'is_private', 'is_favorite', 'is_milestone',
])]
class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'is_private' => 'boolean',
            'is_favorite' => 'boolean',
            'is_milestone' => 'boolean',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** The guardian who wrote it. */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(JournalEntryMedia::class)->orderBy('sort_order');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * API_05: the author always sees their own entry; another guardian on
     * the same child sees it when it is not private, or when it is private
     * and they hold `has_full_photo_access` on that child.
     *
     * @param  Builder<JournalEntry>  $query
     */
    public function scopeVisibleTo($query, Guardian $guardian): void
    {
        $linked = fn () => DB::table('child_guardian')->where('guardian_id', $guardian->getKey());

        $query->whereIn('child_id', $linked()->select('child_id'))
            ->where(fn ($q) => $q
                ->where('guardian_id', $guardian->getKey())
                ->orWhere('is_private', false)
                ->orWhereIn('child_id', $linked()->where('has_full_photo_access', true)->select('child_id')));
    }

    public function isVisibleTo(Guardian $guardian): bool
    {
        if ($this->guardian_id === $guardian->getKey()) {
            return true;
        }

        $access = $guardian->accessTo($this->child_id);

        if ($access === null) {
            return false;
        }

        return ! $this->is_private || (bool) $access['has_full_photo_access'];
    }
}
