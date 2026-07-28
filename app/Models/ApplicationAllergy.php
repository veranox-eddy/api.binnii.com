<?php

namespace App\Models;

use App\Enums\AllergySeverity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['application_id', 'note', 'severity'])]
class ApplicationAllergy extends Model
{
    protected function casts(): array
    {
        return [
            'severity' => AllergySeverity::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
