<?php

// Mirrors app.binnii.com/app/Models/LoginHandoff.php — schema owner is app.binnii.com. Keep in sync.

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One-shot cross-domain sign-in ticket. Tokens are stored sha256-hashed. */
#[Fillable(['user_id', 'token_hash', 'expires_at', 'issued_ip', 'redirect_to'])]
class LoginHandoff extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
