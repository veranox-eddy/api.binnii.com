<?php

namespace App\Models;

use Database\Factories\HealthLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'classroom_id', 'staff_id', 'logged_at', 'type', 'value', 'notes'])]
class HealthLog extends Model
{
    /** @use HasFactory<HealthLogFactory> */
    use HasFactory;

    /** Entry kinds per the schema doc. */
    public const array TYPES = ['symptom', 'temperature', 'medication'];

    /**
     * The 11-code health legend (me-health.html): symptom label => code.
     */
    public const array SYMPTOMS = [
        'Runny nose' => 'N',
        'Cough' => 'G',
        'Diarrhea' => 'D',
        'Fever' => 'F',
        'Vomiting' => 'M',
        'Breathing abnormally' => 'B',
        'Skin rash' => 'S',
        'Cuts/scratches' => 'C',
        'Bruises' => 'U',
        'Behavior' => 'V',
        'Other' => 'O',
    ];

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
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

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** Legend code for the weekly matrix (temperature reads as Fever check). */
    public function code(): string
    {
        return match ($this->type) {
            'symptom' => self::SYMPTOMS[$this->value] ?? 'O',
            'temperature' => 'F',
            default => 'O',
        };
    }
}
