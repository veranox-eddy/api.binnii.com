<?php

namespace App\Models;

use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['center_id', 'title', 'description', 'tags'])]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    /** Category tags offered by the library (curr-library.html). */
    public const array TAGS = ['Sensory', 'Social', 'Art', 'Music', 'Outdoor', 'Literacy', 'Math', 'Play', 'Language'];

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return array<int, string> */
    public function tagList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->tags))));
    }
}
