<?php

namespace App\Http\Requests\Api;

use App\Models\Guardian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCrewMemberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nickname' => ['sometimes', 'nullable', 'string', 'max:80'],
            'relationship' => ['sometimes', 'required', Rule::in(Guardian::CREW_RELATIONSHIPS)],
            'is_account_admin' => ['sometimes', 'boolean'],
            'has_full_photo_access' => ['sometimes', 'boolean'],
            'email' => ['sometimes', 'required', 'email'],
        ];
    }
}
