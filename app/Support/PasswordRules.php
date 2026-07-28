<?php

namespace App\Support;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

/**
 * Mirrors the admin console's Fortify PasswordValidationRules trait, but at
 * the parent portal's 12-character minimum (API_03). Every place a guardian
 * sets a password goes through here so the three flows — activation, reset,
 * settings — can never drift apart.
 */
trait PasswordRules
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::min(config('parent.min_password_length')), 'confirmed'];
    }
}
