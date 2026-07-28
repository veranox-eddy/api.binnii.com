<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_id', 'name', 'relationship'])]
class StaffEmergencyContact extends Model
{
    /**
     * Relationship options from the staff form (a different list from
     * Guardian::RELATIONSHIPS, which serves the child forms).
     */
    public const array RELATIONSHIPS = [
        'Spouse',
        'Parent/Guardian',
        'Family member',
        'Friend',
        'Primary healthcare practitioner',
        'Other',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
