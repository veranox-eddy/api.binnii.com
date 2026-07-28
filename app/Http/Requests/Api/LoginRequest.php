<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            // No length rule here on purpose: an existing password shorter
            // than today's minimum must still fail as "invalid credentials",
            // never as a validation error that reveals the rule.
            'password' => ['required', 'string'],
        ];
    }
}
