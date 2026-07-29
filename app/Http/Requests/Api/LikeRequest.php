<?php

namespace App\Http\Requests\Api;

use App\Models\Comment;
use App\Models\JournalEntry;
use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** One request for both POST and DELETE /likes — the body is the same. */
class LikeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'likeable_type' => ['required', Rule::in(['media', 'comment', 'journal_entry'])],
            'likeable_id' => ['required', 'integer'],
        ];
    }

    /** The morph aliases are the API's contract, so resolve through them. */
    public function likeable(): Media|Comment|JournalEntry
    {
        /** @var class-string<Media|Comment|JournalEntry> $class */
        $class = Relation::getMorphedModel($this->string('likeable_type')->value());

        return $class::query()->findOrFail($this->integer('likeable_id'));
    }
}
