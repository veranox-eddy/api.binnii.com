<?php

// Mirrors app.binnii.com/app/Models/MarketPlan.php — schema owner is app.binnii.com. Keep in sync.

namespace App\Models;

use App\Enums\PlanKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'market_id', 'plan_key', 'display_name', 'monthly_base_fee',
    'annual_base_fee', 'included_active_children', 'monthly_overage_rate',
    'annual_overage_rate', 'best_for', 'sort_order',
])]
class MarketPlan extends Model
{
    protected function casts(): array
    {
        return [
            'plan_key' => PlanKey::class,
            'monthly_base_fee' => 'decimal:2',
            'annual_base_fee' => 'decimal:2',
            'included_active_children' => 'integer',
            'monthly_overage_rate' => 'decimal:4',
            'annual_overage_rate' => 'decimal:4',
        ];
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }
}
