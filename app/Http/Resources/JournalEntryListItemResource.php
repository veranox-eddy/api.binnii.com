<?php

namespace App\Http\Resources;

use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The tabular entries list (S09) — one compact row per journal entry.
 *
 * @mixin JournalEntry
 */
class JournalEntryListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('guardian');
        $isAuthor = $viewer !== null && $this->guardian_id === $viewer->getKey();

        return [
            'id' => $this->id,
            'added_by' => $isAuthor ? 'Me' : $this->guardian?->fullName(),
            'date' => $this->entry_date->format('n/j/y'),
            'entry_date' => $this->entry_date->toDateString(),
            'title' => $this->title,
            'is_private' => $this->is_private,
            'is_favorite' => $this->is_favorite,
            'is_milestone' => $this->is_milestone,
            'has_comments' => false,
            'has_media' => $this->media_count > 0,
        ];
    }
}
