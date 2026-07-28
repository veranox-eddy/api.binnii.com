<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Compose → Template Options (db-compose.html). Insert copies the subject
 * and body into the form; the templates themselves are per center.
 */
#[Fillable(['center_id', 'name', 'subject', 'body'])]
class MessageTemplate extends Model
{
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
