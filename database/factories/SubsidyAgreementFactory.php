<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\SubsidyAgreement;
use App\Models\SubsidyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubsidyAgreement>
 */
class SubsidyAgreementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'subsidy_program_id' => SubsidyProgram::factory(),
            'days_approved' => 5,
            'days_approved_unit' => 'full_days',
            'days_approved_period' => 'week',
            'status' => 'active',
        ];
    }
}
