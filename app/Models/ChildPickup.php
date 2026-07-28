<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'name', 'phone', 'details'])]
class ChildPickup extends Model
{
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }
}
