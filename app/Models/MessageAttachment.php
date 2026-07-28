<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['message_id', 'file_path', 'original_name', 'size'])]
class MessageAttachment extends Model
{
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /** Human-readable size for the thread view ("1.2 MB"). */
    public function sizeForHumans(): string
    {
        return $this->size >= 1048576
            ? round($this->size / 1048576, 1).' MB'
            : max(1, (int) round($this->size / 1024)).' KB';
    }
}
