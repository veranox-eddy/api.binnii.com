<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Text and flags only. Media edits are out of scope for v1 (API_05) — the
 * SPA deletes and re-creates an entry to change its photos.
 */
class UpdateJournalEntryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'is_private' => ['boolean'],
            'is_favorite' => ['boolean'],
            'is_milestone' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_private' => $this->boolean('is_private'),
            'is_favorite' => $this->boolean('is_favorite'),
            'is_milestone' => $this->boolean('is_milestone'),
        ]);
    }
}
