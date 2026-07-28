<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['classroom_id', 'name', 'color', 'sort_order'])]
class WeeklyRoutine extends Model
{
    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WeeklyPlanItem::class);
    }

    /** The cell's top edge; None (null) falls back to the wireframe grey. */
    public function borderColor(): string
    {
        return $this->color ?: '#cccccc';
    }

    /** The wireframe's six default experiences (name + color). */
    public const array DEFAULTS = [
        ['Circle Time', '#e79a86'],
        ['Choice Time', '#edc94e'],
        ['Whole Group', '#cdd257'],
        ['Outdoor Play', '#9fce6a'],
        ['Read-Aloud', '#a7d7e6'],
        ['Small Group', '#c3b3e0'],
    ];
}
