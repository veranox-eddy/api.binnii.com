<?php

namespace App\Models;

use App\Enums\DevelopmentalFramework;
use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'center_id', 'name', 'display_name', 'external_ref', 'age_range_id',
    'desired_capacity', 'student_staff_ratio', 'developmental_framework',
    'login_username', 'is_floating', 'photo_sharing_enabled', 'is_active',
])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'desired_capacity' => 'integer',
            'developmental_framework' => DevelopmentalFramework::class,
            'is_floating' => 'boolean',
            'photo_sharing_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function ageRange(): BelongsTo
    {
        return $this->belongsTo(AgeRange::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(ClassroomAlert::class)->orderBy('remind_at');
    }

    /** The weekly planner's routine rows, in the order they are drawn. */
    public function weeklyRoutines(): HasMany
    {
        return $this->hasMany(WeeklyRoutine::class)->orderBy('sort_order');
    }
}
