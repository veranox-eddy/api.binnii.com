<?php

namespace App\Http\Requests\Api;

use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    use PasswordRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => $this->passwordRules(),
        ];
    }
}
