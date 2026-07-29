<?php

namespace App\Http\Requests\Api;

use App\Models\Guardian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCrewMemberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'members' => ['required', 'array', 'min:1', 'max:10'],
            'members.*.email' => ['required', 'email'],
            'members.*.name' => ['required', 'string', 'max:150'],
            'members.*.relationship' => ['required', Rule::in(Guardian::CREW_RELATIONSHIPS)],
            'members.*.is_account_admin' => ['boolean'],
        ];
    }

    /**
     * The form posts one member; "Add Another Crew Member" posts `members`.
     * Normalize to the array shape so the rules cover both.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('members')) {
            $this->merge(['members' => [$this->only(['email', 'name', 'relationship', 'is_account_admin'])]]);
        }
    }
}
