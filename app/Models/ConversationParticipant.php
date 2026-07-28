<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['conversation_id', 'participant_type', 'participant_id', 'role'])]
class ConversationParticipant extends Model
{
    public $timestamps = false;

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function participant(): MorphTo
    {
        return $this->morphTo();
    }
}
