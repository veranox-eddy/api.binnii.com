<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Journal media live on the public disk and are served straight from it
     * — unlike center media, which is streamed through an authorized
     * download because the center owns who may see it.
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->file_path));
    }
}
