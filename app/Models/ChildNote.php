<?php

namespace App\Models;

use App\Enums\ChildNoteCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'category', 'body'])]
class ChildNote extends Model
{
    protected function casts(): array
    {
        return [
            'category' => ChildNoteCategory::class,
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
