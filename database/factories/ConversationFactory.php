<?php

namespace Database\Factories;

use App\Enums\ConversationType;
use App\Models\Center;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'center_id' => Center::factory(),
            'subject' => fake()->sentence(4),
            'type' => ConversationType::Message,
            'created_by' => User::factory(),
        ];
    }
}
