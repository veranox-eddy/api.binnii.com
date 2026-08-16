<?php

// Mirrors app.binnii.com/app/Models/PlatformSetting.php — schema owner is app.binnii.com. Keep in sync.

namespace App\Models;

use App\Enums\PlanKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Single-row table; no settings UI this round. */
#[Fillable(['free_trial_enabled', 'default_trial_length_days', 'trial_plan_entitlement'])]
class PlatformSetting extends Model
{
    protected function casts(): array
    {
        return [
            'free_trial_enabled' => 'boolean',
            'default_trial_length_days' => 'integer',
            'trial_plan_entitlement' => PlanKey::class,
        ];
    }

    /** The one row, memoized per request (dozens of call sites per page). */
    public static function current(): self
    {
        return once(fn () => self::firstOrFail());
    }
}
