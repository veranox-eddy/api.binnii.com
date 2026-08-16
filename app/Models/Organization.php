<?php

// Mirrors app.binnii.com/app/Models/Organization.php — schema owner is app.binnii.com. Keep in sync.

namespace App\Models;

use App\Enums\OrganizationLifecycleStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'status', 'market_id', 'lifecycle_status', 'billing_timezone'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'lifecycle_status' => OrganizationLifecycleStatus::class,
            'is_test_account' => 'boolean',
        ];
    }

    public function centers(): HasMany
    {
        return $this->hasMany(Center::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /** P-F50: test accounts are a hard back-end exclusion, not a UI filter. */
    public function scopeNotTestAccounts(Builder $query): Builder
    {
        return $query->where('is_test_account', false);
    }

    /**
     * Drives the Setup-required guidance card. Organizations may have zero
     * centers (spec v2 dropped Platform PRD F-42's precondition) — the
     * first center is simply created on /centers whenever the admin gets
     * to it.
     */
    public function needsFirstCenter(): bool
    {
        return $this->centers()->count() === 0;
    }
}
