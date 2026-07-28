<?php

namespace App\Models;

use App\Enums\EntryType;
use Database\Factories\EntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'classroom_id', 'staff_id', 'type', 'occurred_at', 'payload'])]
class Entry extends Model
{
    /** @use HasFactory<EntryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => EntryType::class,
            'occurred_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function summary(): string
    {
        return $this->type->summarize($this->payload ?? []);
    }

    public function qty(): ?string
    {
        return $this->type->qty($this->payload ?? []);
    }
}
