<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['guardian_id', 'likeable_type', 'likeable_id', 'created_at'])]
class Like extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Toggle a guardian's like on media, a comment, or a journal entry
     * (unique per pair). Parents like from the parent app; this is the
     * shared domain seam.
     */
    public static function toggle(Guardian $guardian, Media|Comment|JournalEntry $likeable): bool
    {
        $existing = $likeable->likes()->where('guardian_id', $guardian->id)->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        $likeable->likes()->create(['guardian_id' => $guardian->id, 'created_at' => now()]);

        return true;
    }
}
