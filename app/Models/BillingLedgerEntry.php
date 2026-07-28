<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'entry_date', 'description', 'type', 'amount'])]
class BillingLedgerEntry extends Model
{
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'type' => LedgerEntryType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    /** Charges add to the balance owed; payments and credits reduce it. */
    public function signedAmount(): float
    {
        return $this->type === LedgerEntryType::Charge ? (float) $this->amount : -(float) $this->amount;
    }
}
