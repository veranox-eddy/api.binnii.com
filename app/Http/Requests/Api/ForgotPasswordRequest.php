<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Deliberately no `exists` rule — whether an address belongs to a
        // guardian is not something an unauthenticated caller gets to probe.
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
        ];
    }
}
