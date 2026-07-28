<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sharing an entry with the Crew is the inverse of `is_private`. The SPA may
 * post the state it wants; omitting it toggles.
 */
class ShareJournalEntryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shared' => ['nullable', 'boolean'],
        ];
    }
}
