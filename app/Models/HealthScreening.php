<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['center_id', 'staff_administered_enabled', 'family_administered_enabled', 'questions'])]
class HealthScreening extends Model
{
    /** Default symptom questions (health-screening.html). */
    public const array DEFAULT_QUESTIONS = [
        'Temperature above 37.7C',
        'Sore throat',
        'Cough',
        'Difficulty breathing',
        'Diarrhea or vomiting',
        'Loss of taste or smell',
        'New onset of fever or severe headache',
    ];

    protected $attributes = [
        'staff_administered_enabled' => false,
        'family_administered_enabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'staff_administered_enabled' => 'boolean',
            'family_administered_enabled' => 'boolean',
            'questions' => 'array',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * The center's screening config for display — an unsaved default
     * instance when none exists yet, so GET requests never write.
     */
    public static function forCenter(Center $center): self
    {
        return $center->healthScreening
            ?? new self(['center_id' => $center->id, 'questions' => self::DEFAULT_QUESTIONS]);
    }
}
