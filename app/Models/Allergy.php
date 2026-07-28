<?php

namespace App\Models;

use App\Enums\AllergySeverity;
use Database\Factories\AllergyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'note', 'severity'])]
class Allergy extends Model
{
    /** @use HasFactory<AllergyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'severity' => AllergySeverity::class,
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
