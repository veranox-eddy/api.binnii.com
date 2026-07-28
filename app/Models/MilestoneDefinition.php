<?php

namespace App\Models;

use App\Enums\MilestoneAgeGroup;
use App\Enums\MilestoneCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * Either a seeded global milestone (center_id and child_id null) or one a
 * family added themselves via "Add Your Own!" (child_id set, is_custom).
 */
#[Fillable([
    'center_id', 'child_id', 'age_group', 'category', 'name', 'sort_order', 'is_custom',
])]
class MilestoneDefinition extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'age_group' => MilestoneAgeGroup::class,
            'category' => MilestoneCategory::class,
            'is_custom' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function childMilestones(): HasMany
    {
        return $this->hasMany(ChildMilestone::class);
    }

    /**
     * The list one child sees: the global defaults, the center's own
     * additions, and that child's custom entries — nobody else's.
     *
     * @param  Builder<MilestoneDefinition>  $query
     */
    public function scopeForChild($query, Child $child): void
    {
        // Each arm names child_id explicitly: a row scoped to a sibling must
        // not slip in through the center arm just because it also carries a
        // center_id.
        $query->where(fn ($q) => $q
            ->where(fn ($qq) => $qq->whereNull('center_id')->whereNull('child_id'))
            ->orWhere(fn ($qq) => $qq->where('center_id', $child->center_id)->whereNull('child_id'))
            ->orWhere('child_id', $child->getKey()));
    }
}
