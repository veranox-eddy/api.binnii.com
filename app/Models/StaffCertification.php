<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_id', 'type', 'label', 'expiry_date', 'no_expiry', 'file_path'])]
class StaffCertification extends Model
{
    /**
     * Certificate type options offered in the wireframe select
     * (logins-config.html / db-add-teacher.html certification block).
     */
    public const array TYPES = [
        'CPR',
        'RECE',
        'COVID-19 Documentation',
        'Background Checks',
        'First Aid Certification',
        'AED (Automated External Defibrillator) Training',
        'Child Development Permit',
        'Health Certificate/Clearance',
        'Teaching Certificate/License',
        'Professional Development/CEU',
        "Food Handler's Permit",
        'Medication Administration Training',
        'Other',
    ];

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
