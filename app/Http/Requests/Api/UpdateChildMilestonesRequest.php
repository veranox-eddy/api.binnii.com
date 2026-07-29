<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChildMilestonesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.milestone_definition_id' => ['required', 'integer', 'exists:milestone_definitions,id'],
            'items.*.achieved_on' => ['nullable', 'date_format:Y-m-d'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
