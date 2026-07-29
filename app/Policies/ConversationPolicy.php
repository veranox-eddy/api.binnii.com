<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Guardian;
use Illuminate\Auth\Access\Response;

/**
 * A guardian's message center holds exactly the threads they participate
 * in. Reading an outside thread 404s — its existence is nobody's business —
 * while writing to one is a plain 403 (API_09).
 */
class ConversationPolicy
{
    public function view(Guardian $guardian, Conversation $conversation): Response
    {
        return $conversation->hasGuardianParticipant($guardian)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function reply(Guardian $guardian, Conversation $conversation): Response
    {
        return $this->participantOnly($guardian, $conversation);
    }

    public function archive(Guardian $guardian, Conversation $conversation): Response
    {
        return $this->participantOnly($guardian, $conversation);
    }

    private function participantOnly(Guardian $guardian, Conversation $conversation): Response
    {
        return $conversation->hasGuardianParticipant($guardian)
            ? Response::allow()
            : Response::deny('You are not part of this conversation.');
    }
}
