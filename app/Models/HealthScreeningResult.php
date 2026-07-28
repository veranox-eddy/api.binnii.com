<?php

namespace App\Models;

use App\Enums\ScreeningAdministeredBy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Filled by the classroom dashboard / parent app (out of scope for the
 * admin console) — every question answered "No" = passed.
 */
#[Fillable(['child_id', 'screened_on', 'administered_by', 'passed', 'answers'])]
class HealthScreeningResult extends Model
{
    protected function casts(): array
    {
        return [
            'screened_on' => 'date',
            'administered_by' => ScreeningAdministeredBy::class,
            'passed' => 'boolean',
            'answers' => 'array',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
