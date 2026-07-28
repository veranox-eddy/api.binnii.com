<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            // Blank in the UI means today — the SPA may simply omit it.
            'entry_date' => ['nullable', 'date_format:Y-m-d'],
            'is_private' => ['boolean'],
            'is_favorite' => ['boolean'],
            'is_milestone' => ['boolean'],
            'media' => ['required', 'array', 'min:1', 'max:20'],
            'media.*' => ['file', 'mimetypes:image/jpeg,image/png,image/heic,image/webp,video/mp4,video/quicktime', 'max:102400'],
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
