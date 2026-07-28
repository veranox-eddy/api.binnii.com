<?php

namespace Database\Factories;

use App\Enums\GuardianRegistrationStatus;
use App\Models\Center;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    /** The password `registered()` sets, for tests that need to log in. */
    public const string PASSWORD = 'correct-horse-battery';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile_phone' => fake()->phoneNumber(),
        ];
    }

    /** A guardian who has been through activation and can log in. */
    public function registered(?string $password = null): static
    {
        return $this->state(fn () => [
            'password' => $password ?? self::PASSWORD,
            'registration_status' => GuardianRegistrationStatus::Registered,
            'email_verified_at' => now(),
        ]);
    }

    /** Invited by the center, but the welcome link has not been used yet. */
    public function invited(): static
    {
        return $this->state(fn () => [
            'password' => null,
            'registration_status' => GuardianRegistrationStatus::Invited,
            'invited_at' => now(),
        ]);
    }
}
