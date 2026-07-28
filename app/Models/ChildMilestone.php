<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * One child reaching one milestone. `custom_name` carries the label when the
 * family logged something without a definition behind it.
 */
#[Fillable([
    'child_id', 'milestone_definition_id', 'custom_name', 'achieved_on',
    'description', 'recorded_by_guardian_id',
])]
class ChildMilestone extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'achieved_on' => 'date',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(MilestoneDefinition::class, 'milestone_definition_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'recorded_by_guardian_id');
    }

    public function name(): string
    {
        return $this->custom_name ?? $this->definition?->name ?? '';
    }
}
