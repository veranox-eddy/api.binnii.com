<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['weekly_plan_id', 'weekly_routine_id', 'plan_date', 'activity_id', 'notes', 'sort_order'])]
class WeeklyPlanItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'plan_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(WeeklyPlan::class, 'weekly_plan_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** The routine row (grid column-within-day) this item sits in. */
    public function routine(): BelongsTo
    {
        return $this->belongsTo(WeeklyRoutine::class, 'weekly_routine_id');
    }

    public function title(): string
    {
        return $this->activity?->title ?? (string) $this->notes;
    }
}
