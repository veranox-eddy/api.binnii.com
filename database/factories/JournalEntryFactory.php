<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Guardian;
use App\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'guardian_id' => Guardian::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'entry_date' => now()->toDateString(),
            'is_private' => false,
            'is_favorite' => false,
            'is_milestone' => false,
        ];
    }

    /** Not shared with the rest of the Crew. */
    public function private(): static
    {
        return $this->state(fn () => ['is_private' => true]);
    }
}
