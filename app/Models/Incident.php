<?php

namespace App\Models;

use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'child_id', 'classroom_id', 'type_of_incident', 'occurred_at', 'description',
    'parent_notified', 'parent_notified_at', 'parent_signature', 'reported_by',
])]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'parent_notified' => 'boolean',
            'parent_notified_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reported_by');
    }

    public function staffPresent(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'incident_staff');
    }
}
