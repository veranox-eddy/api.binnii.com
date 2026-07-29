<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailSettingsRequest extends FormRequest
{
    /** The email languages the settings screen offers. */
    public const array LANGUAGES = ['en', 'zh-TW'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $guardian = $this->user('guardian');

        return [
            // "Retype Email" — the SPA posts email_confirmation.
            // Uniqueness is per center, not global: one parent with children
            // at two centers legitimately owns two guardian rows (API_03).
            'email' => ['required', 'email', 'confirmed',
                Rule::unique('guardians', 'email')
                    ->where('center_id', $guardian->center_id)
                    ->ignore($guardian->getKey()),
            ],
            'receive_fewer_emails' => ['boolean'],
            'email_language' => [Rule::in(self::LANGUAGES)],
        ];
    }
}
