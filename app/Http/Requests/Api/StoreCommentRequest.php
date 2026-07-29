<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'child_id' => ['required', 'integer', 'exists:children,id'],
            'body' => ['required', 'string', 'max:2000'],
            // Exactly one target: a center photo or a family journal entry.
            'media_id' => ['integer', 'required_without:journal_entry_id', 'prohibits:journal_entry_id', 'exists:media,id'],
            'journal_entry_id' => ['integer', 'required_without:media_id', 'exists:journal_entries,id'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ];
    }
}
