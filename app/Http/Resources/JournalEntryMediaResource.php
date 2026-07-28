<?php

namespace App\Http\Resources;

use App\Models\JournalEntryMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JournalEntryMedia
 */
class JournalEntryMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->media_type->value,
            'url' => $this->url,
            'thumb_url' => $this->url,
            'sort_order' => $this->sort_order,
        ];
    }
}
