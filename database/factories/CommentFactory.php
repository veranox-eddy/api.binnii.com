<?php

namespace Database\Factories;

use App\Enums\CommentThreadSubject;
use App\Models\Comment;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guardian_id' => Guardian::factory(),
            'thread_subject' => CommentThreadSubject::Post,
            'body' => fake()->sentence(),
        ];
    }
}
