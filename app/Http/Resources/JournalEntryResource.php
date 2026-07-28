<?php

namespace App\Http\Resources;

use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JournalEntry
 */
class JournalEntryResource extends JsonResource
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
            'child_id' => $this->child_id,
            'title' => $this->title,
            'description' => $this->description,
            'entry_date' => $this->entry_date->toDateString(),
            'is_private' => $this->is_private,
            'is_favorite' => $this->is_favorite,
            'is_milestone' => $this->is_milestone,
            'is_mine' => $isAuthor,
            'author' => [
                'type' => 'guardian',
                'name' => $isAuthor ? 'Me' : $this->guardian?->fullName(),
            ],
            'media' => JournalEntryMediaResource::collection($this->whenLoaded('media')),
            // See JournalFeed: nothing in `comments` can reference a journal
            // entry until API_06 settles that schema.
            'comment_count' => 0,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
