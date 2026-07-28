<?php

namespace App\Models;

use Database\Factories\SubsidyProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['center_id', 'name', 'provider', 'details'])]
class SubsidyProgram extends Model
{
    /** @use HasFactory<SubsidyProgramFactory> */
    use HasFactory;

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(SubsidyAgreement::class);
    }
}
