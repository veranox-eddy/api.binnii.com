<?php

namespace App\Models;

use App\Enums\DaysApprovedPeriod;
use App\Enums\DaysApprovedUnit;
use App\Enums\MaxAbsentPeriod;
use Database\Factories\SubsidyAgreementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'child_id', 'subsidy_program_id', 'days_approved', 'days_approved_unit',
    'days_approved_period', 'max_absent_days', 'max_absent_period',
    'covid_days_count_absent', 'start_date', 'end_date', 'additional_info', 'status',
])]
class SubsidyAgreement extends Model
{
    /** @use HasFactory<SubsidyAgreementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'days_approved' => 'integer',
            'days_approved_unit' => DaysApprovedUnit::class,
            'days_approved_period' => DaysApprovedPeriod::class,
            'max_absent_days' => 'integer',
            'max_absent_period' => MaxAbsentPeriod::class,
            'covid_days_count_absent' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(SubsidyProgram::class, 'subsidy_program_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubsidyPayment::class);
    }

    /** "5 full days per week" — as the profile block shows it. */
    public function daysSummary(): string
    {
        return trim(($this->days_approved ?? '').' '.$this->days_approved_unit->label().' '.$this->days_approved_period->label());
    }
}
