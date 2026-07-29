<?php

namespace App\Http\Requests\Api;

use App\Enums\MilestoneAgeGroup;
use App\Enums\MilestoneCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomMilestoneRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'age_group' => ['required', Rule::in(MilestoneAgeGroup::values())],
            'category' => ['required', Rule::in(MilestoneCategory::values())],
            'name' => ['required', 'string', 'max:120'],
            'achieved_on' => ['nullable', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
