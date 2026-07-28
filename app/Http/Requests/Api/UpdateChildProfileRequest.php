<?php

namespace App\Http\Requests\Api;

use App\Enums\ChildGender;
use App\Models\Guardian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChildProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            // No `before` rule: an expecting family adds the child before
            // the birth, so a due date in the future is valid.
            'birthday' => ['required', 'date_format:Y-m-d'],
            'gender' => ['required', Rule::enum(ChildGender::class)],
            'photo' => ['nullable', 'image', 'max:8192'],
            'relationship' => ['nullable', 'string', Rule::in(Guardian::CREW_RELATIONSHIPS)],
            'nickname' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * The SPA's date picker posts three selects. Accept either those or a
     * ready-made `birthday`, and validate one field either way.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('birthday') && $this->filled(['birth_year', 'birth_month', 'birth_day'])) {
            $this->merge([
                'birthday' => sprintf(
                    '%04d-%02d-%02d',
                    (int) $this->input('birth_year'),
                    (int) $this->input('birth_month'),
                    (int) $this->input('birth_day'),
                ),
            ]);
        }
    }
}
