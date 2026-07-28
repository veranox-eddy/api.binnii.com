<?php

namespace App\Models;

use App\Enums\AbsenceReason;
use Database\Factories\AbsenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'start_date', 'end_date', 'reason', 'note'])]
class Absence extends Model
{
    /** @use HasFactory<AbsenceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reason' => AbsenceReason::class,
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /**
     * Absences covering the given date (single-day rows have no end_date).
     *
     * @param  Builder<Absence>  $query
     */
    public function scopeCovering(Builder $query, string $date): void
    {
        $query->whereDate('start_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('end_date')->whereDate('start_date', $date)
                ->orWhereDate('end_date', '>=', $date));
    }

    /**
     * Absences touching any day of the given range (open-ended rows count
     * from their start date).
     *
     * @param  Builder<Absence>  $query
     */
    public function scopeOverlapping(Builder $query, string $start, string $end): void
    {
        $query->whereDate('start_date', '<=', $end)
            ->where(fn ($q) => $q->whereNull('end_date')->whereDate('start_date', '>=', $start)
                ->orWhereDate('end_date', '>=', $start));
    }
}
