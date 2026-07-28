<?php

namespace App\Models;

use Database\Factories\SubsidyPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * `difference` is a stored generated column (received - estimated) — never
 * written from the application.
 */
#[Fillable([
    'subsidy_agreement_id', 'payment_period', 'description', 'estimated_amount',
    'received_amount', 'payment_date', 'action_taken',
])]
class SubsidyPayment extends Model
{
    /** @use HasFactory<SubsidyPaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'estimated_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'difference' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(SubsidyAgreement::class, 'subsidy_agreement_id');
    }
}
