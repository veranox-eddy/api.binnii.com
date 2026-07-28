<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * Parent-side email toggles (API_11). Distinct from the center-side
 * NotificationPreference model, which is per staff user and channel.
 */
#[Fillable([
    'guardian_id', 'report_started', 'report_ready', 'new_entry',
    'new_photo', 'new_comment', 'classroom_photos',
])]
class GuardianNotificationPreference extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'report_started' => 'boolean',
            'report_ready' => 'boolean',
            'new_entry' => 'boolean',
            'new_photo' => 'boolean',
            'new_comment' => 'boolean',
            'classroom_photos' => 'boolean',
        ];
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
}
