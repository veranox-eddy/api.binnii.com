<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['journal_entry_id', 'media_type', 'file_path', 'sort_order'])]
class JournalEntryMedia extends Model
{
    use HasFactory;

    protected $table = 'journal_entry_media';

    protected function casts(): array
    {
        return [
            'media_type' => MediaType::class,
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
