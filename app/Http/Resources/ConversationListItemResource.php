<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One inbox row (S15). Needs `participants.participant` and `center`
 * loaded, plus the `last_message_at` / `unread` aggregates from the
 * controller query.
 *
 * @mixin Conversation
 */
class ConversationListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'time' => $this->last_message_at,
            'from' => $this->fromLine($request),
            'subject' => $this->subject,
            'unread' => (bool) $this->unread,
        ];
    }

    /**
     * The other party: everyone in the thread who is not the viewer. A
     * thread the guardian started alone falls back to the center's name.
     */
    private function fromLine(Request $request): string
    {
        $viewer = $request->user('guardian');

        $others = $this->participants
            ->reject(fn (ConversationParticipant $participant) => $viewer !== null
                && $participant->participant_type === 'guardian'
                && (int) $participant->participant_id === $viewer->getKey())
            ->map(fn (ConversationParticipant $participant) => match (true) {
                $participant->participant === null => null,
                method_exists($participant->participant, 'fullName') => $participant->participant->fullName(),
                default => $participant->participant->name,
            })
            ->filter()
            ->unique();

        return $others->isNotEmpty()
            ? $others->implode(', ')
            : (string) $this->center?->name;
    }
}
