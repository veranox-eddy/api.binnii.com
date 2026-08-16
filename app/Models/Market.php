<?php

// Mirrors app.binnii.com/app/Models/Market.php — schema owner is app.binnii.com. Keep in sync.

namespace App\Models;

use App\Enums\MarketSource;
use App\Enums\PlanKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Local stub of the Platform market contract — see
 * specs/saas/build-signup-free-trial.md §2.2. Prices, currencies and
 * discount rates are always read from here, never hard-coded in views.
 */
#[Fillable([
    'code', 'name', 'country_code', 'currency', 'annual_discount_rate',
    'tax_name', 'tax_rate', 'tax_confirmed_at', 'tax_notice', 'is_active',
    'is_fallback', 'source', 'contract_version',
])]
class Market extends Model
{
    protected function casts(): array
    {
        return [
            'annual_discount_rate' => 'decimal:3',
            'tax_rate' => 'decimal:4',
            'tax_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'source' => MarketSource::class,
        ];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(MarketPlan::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function planFor(PlanKey $key): ?MarketPlan
    {
        return $this->plans->firstWhere('plan_key', $key);
    }
}
