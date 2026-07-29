<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('guardian');

        return [
            'id' => $this->id,
            'author_name' => $this->authorName(),
            'author_type' => $this->guardian_id !== null ? 'guardian' : 'center',
            'is_mine' => $viewer !== null && $this->guardian_id === $viewer->getKey(),
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'parent_id' => $this->parent_id,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
