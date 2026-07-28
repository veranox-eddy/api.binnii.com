<?php

namespace App\Models;

use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * A guardian's own journal entry about their child. Never surfaced to the
 * center — the staff-authored counterpart is Entry/Media.
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

    /**
     * Entries this guardian may read: their own children's, minus the
     * private ones another guardian on the same child wrote.
     *
     * @param  Builder<JournalEntry>  $query
     */
    public function scopeVisibleTo($query, Guardian $guardian): void
    {
        $query->whereIn('child_id', $guardian->children()->select('children.id'))
            ->where(fn ($q) => $q->where('is_private', false)
                ->orWhere('guardian_id', $guardian->getKey()));
    }
}
