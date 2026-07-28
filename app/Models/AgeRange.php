<?php

namespace App\Models;

use Database\Factories\AgeRangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['center_id', 'label', 'min_months', 'max_months'])]
class AgeRange extends Model
{
    /** @use HasFactory<AgeRangeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'min_months' => 'integer',
            'max_months' => 'integer',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
