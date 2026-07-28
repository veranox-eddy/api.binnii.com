<?php

namespace App\Models;

use App\Enums\ApplicationContactType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'application_id', 'type', 'first_name', 'last_name', 'relationship', 'email',
    'phone', 'address_line1', 'address_line2', 'city', 'state', 'country', 'zip',
])]
class ApplicationContact extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ApplicationContactType::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
