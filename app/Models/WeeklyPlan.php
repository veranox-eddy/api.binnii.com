<?php

namespace App\Models;

use Database\Factories\WeeklyPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['classroom_id', 'week_start_date'])]
class WeeklyPlan extends Model
{
    /** @use HasFactory<WeeklyPlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WeeklyPlanItem::class)->orderBy('sort_order');
    }

    /** One plan per classroom per week (whereDate — date-cast pitfall). */
    public static function forWeek(int $classroomId, string $weekStart): self
    {
        return self::where('classroom_id', $classroomId)->whereDate('week_start_date', $weekStart)->first()
            ?? self::create(['classroom_id' => $classroomId, 'week_start_date' => $weekStart]);
    }
}
