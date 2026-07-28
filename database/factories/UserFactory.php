<?php

namespace Database\Factories;

use App\Enums\AccessLevel;
use App\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'type' => UserType::Admin,
            'access_level' => AccessLevel::Center,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function orgAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::Admin,
            'access_level' => AccessLevel::Organization,
        ]);
    }
}
