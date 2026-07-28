<?php

namespace App\Models;

use App\Enums\StaffNoteCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_id', 'category', 'body'])]
class StaffNote extends Model
{
    protected function casts(): array
    {
        return [
            'category' => StaffNoteCategory::class,
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
