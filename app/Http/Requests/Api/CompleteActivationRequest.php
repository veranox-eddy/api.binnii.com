<?php

namespace App\Http\Requests\Api;

use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class CompleteActivationRequest extends FormRequest
{
    use PasswordRules;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            // One "Name" input, split on the last word — see Guardian::splitName().
            'name' => ['required', 'string', 'max:160'],
            'password' => $this->passwordRules(),
        ];
    }
}
