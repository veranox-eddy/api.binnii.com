<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['staff_enrollment_id', 'weekday'])]
class StaffEnrollmentDay extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StaffEnrollment::class, 'staff_enrollment_id');
    }
}
