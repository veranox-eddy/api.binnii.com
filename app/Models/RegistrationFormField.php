<?php

namespace App\Models;

use App\Enums\RegistrationFormType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'center_id', 'form_type', 'group', 'label', 'input_type',
    'is_required', 'is_hidden', 'is_custom', 'sort_order',
])]
class RegistrationFormField extends Model
{
    /** Field groups per PRD 5.4.1, in display order. */
    public const array GROUPS = [
        'Child information',
        'Parent/Guardian information',
        'Emergency contacts',
        'Allergy and medical notes',
        'Enrollment information',
    ];

    protected function casts(): array
    {
        return [
            'form_type' => RegistrationFormType::class,
            'is_required' => 'boolean',
            'is_hidden' => 'boolean',
            'is_custom' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
