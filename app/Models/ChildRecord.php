<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'type', 'label', 'expiry_date', 'no_expiry', 'file_path'])]
class ChildRecord extends Model
{
    /**
     * Record/form type options — the db-add-child.html select (addRecord()'s
     * non-teacher branch), which is the wireframe these child forms follow.
     */
    public const array TYPES = [
        'Immunizations', 'Incident Report', 'Registration', 'Medical Consent',
        'Permission Form', 'Contact Form', 'Emergency Contacts', 'Other',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'no_expiry' => 'boolean',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
