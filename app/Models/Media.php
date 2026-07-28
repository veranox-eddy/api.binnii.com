<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'center_id', 'classroom_id', 'uploaded_by', 'media_type', 'file_path',
    'caption', 'status', 'sent_at', 'occurred_at',
])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    protected $table = 'media';

    protected function casts(): array
    {
        return [
            'media_type' => MediaType::class,
            'status' => MediaStatus::class,
            'sent_at' => 'datetime',
            'occurred_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'media_child');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}
