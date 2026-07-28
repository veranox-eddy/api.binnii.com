<?php

namespace App\Models;

use App\Enums\ResponseItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['application_id', 'item_type', 'item_id', 'granted', 'signed_at', 'file_path'])]
class ApplicationResponse extends Model
{
    protected function casts(): array
    {
        return [
            'item_type' => ResponseItemType::class,
            'granted' => 'boolean',
            'signed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
