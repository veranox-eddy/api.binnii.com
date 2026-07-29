<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full thread (S15). Needs `messages.sender` + `messages.attachments`
 * loaded.
 *
 * @mixin Conversation
 */
class ConversationThreadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'messages' => MessageResource::collection($this->messages),
        ];
    }
}
