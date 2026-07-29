<?php

namespace App\Http\Requests\Api;

use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordSettingsRequest extends FormRequest
{
    use PasswordRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:guardian'],
            'password' => $this->passwordRules(),
        ];
    }
}
