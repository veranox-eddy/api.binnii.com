<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_id', 'type', 'label', 'expiry_date', 'no_expiry', 'file_path'])]
class StaffRecord extends Model
{
    /** Record/form type options from the staff form's Records block. */
    public const array TYPES = ['Immunizations', 'Degree', 'Other'];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'no_expiry' => 'boolean',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
