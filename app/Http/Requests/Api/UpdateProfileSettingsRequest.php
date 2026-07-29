<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileSettingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // One "Name" input, split on the last word — see Guardian::splitName().
            'name' => ['required', 'string', 'max:160'],
        ];
    }
}
