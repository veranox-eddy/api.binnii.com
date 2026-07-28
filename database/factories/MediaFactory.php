<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\Center;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
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
            'media_type' => MediaType::Photo,
            'file_path' => 'media/'.fake()->uuid().'.jpg',
            'caption' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }
}
