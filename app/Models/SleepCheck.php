<?php

namespace App\Models;

use Database\Factories\SleepCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'classroom_id', 'staff_id', 'checked_at', 'position', 'status'])]
class SleepCheck extends Model
{
    /** @use HasFactory<SleepCheckFactory> */
    use HasFactory;

    public const array POSITIONS = ['Back', 'Side', 'Stomach'];

    public const array STATUSES = ['Sleeping', 'Awake', 'Restless'];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
