<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('guardian');
        $isMine = $viewer !== null
            && $this->sender_type === 'guardian'
            && (int) $this->sender_id === $viewer->getKey();

        return [
            'id' => $this->id,
            'sender_name' => $isMine ? 'Me' : $this->senderName(),
            'sender_type' => $this->sender_type,
            'is_mine' => $isMine,
            'body' => $this->body,
            'attachments' => $this->attachments->map(fn ($attachment) => [
                'url' => Storage::disk('public')->url($attachment->file_path),
                'original_name' => $attachment->original_name,
                'size' => $attachment->size,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
        ];
    }
}
