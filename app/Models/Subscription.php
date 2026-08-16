<?php

// Mirrors app.binnii.com/app/Models/Subscription.php — schema owner is app.binnii.com. Keep in sync.

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethodReadiness;
use App\Enums\PlanKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per Organization. No money math lives here — there is no pricing
 * engine this round; the Platform takes over billing later (C-F27).
 */
#[Fillable([
    'organization_id', 'plan_key', 'billing_cycle', 'effective_date',
    'current_period_start', 'current_period_end', 'renewal_at',
    'is_trialing', 'trial_started_at', 'trial_ends_at', 'trial_plan_key',
    'trial_days_granted', 'payment_method_readiness', 'last_synced_at',
])]
class Subscription extends Model
{
    protected function casts(): array
    {
        return [
            'plan_key' => PlanKey::class,
            'billing_cycle' => BillingCycle::class,
            'effective_date' => 'date',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'renewal_at' => 'date',
            'pending_plan_key' => PlanKey::class,
            'pending_effective_date' => 'date',
            'is_trialing' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'trial_plan_key' => PlanKey::class,
            'trial_days_granted' => 'integer',
            'payment_method_readiness' => PaymentMethodReadiness::class,
            'last_synced_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isTrialing(): bool
    {
        return $this->is_trialing && $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /** For the expiry job that a later round adds (P-F49). */
    public function hasTrialExpired(): bool
    {
        return $this->is_trialing && $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }

    /**
     * Remaining CALENDAR days in the organization's billing timezone
     * (C-F31), never negative. Null when there is no trial end date.
     */
    public function trialDaysRemaining(): ?int
    {
        if ($this->trial_ends_at === null) {
            return null;
        }

        $timezone = $this->organization->billing_timezone;
        $today = now($timezone)->startOfDay();
        $endDay = $this->trial_ends_at->copy()->setTimezone($timezone)->startOfDay();

        return (int) max(0, $today->diffInDays($endDay, false));
    }

    /**
     * The plan whose limits currently apply: the trial-entitlement snapshot
     * while trialing (never presented as "Current plan"), the paid plan
     * otherwise.
     */
    public function entitlementPlan(): ?MarketPlan
    {
        $key = $this->is_trialing ? $this->trial_plan_key : $this->plan_key;

        return $key ? $this->organization->market?->planFor($key) : null;
    }

    public function includedActiveChildren(): ?int
    {
        return $this->entitlementPlan()?->included_active_children;
    }
}
