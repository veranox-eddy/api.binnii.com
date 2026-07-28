<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-group push toggles; a NULL classroom is the "Administrative logins"
 * row (logins-config Mobile Push Notifications).
 */
#[Fillable(['center_id', 'classroom_id', 'new_messages', 'new_comments', 'new_likes'])]
class NotificationPreference extends Model
{
    protected function casts(): array
    {
        return [
            'new_messages' => 'boolean',
            'new_comments' => 'boolean',
            'new_likes' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
