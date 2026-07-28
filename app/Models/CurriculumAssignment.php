<?php

namespace App\Models;

use App\Enums\CurriculumLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['classroom_id', 'curriculum'])]
class CurriculumAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'curriculum' => CurriculumLevel::class,
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }
}
