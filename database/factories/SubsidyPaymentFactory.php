<?php

namespace Database\Factories;

use App\Models\SubsidyAgreement;
use App\Models\SubsidyPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubsidyPayment>
 */
class SubsidyPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subsidy_agreement_id' => SubsidyAgreement::factory(),
            'payment_period' => now()->format('F Y'),
            'estimated_amount' => fake()->randomFloat(2, 200, 900),
            'received_amount' => fake()->randomFloat(2, 200, 900),
            'payment_date' => now()->toDateString(),
        ];
    }
}
