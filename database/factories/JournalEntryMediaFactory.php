<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\JournalEntry;
use App\Models\JournalEntryMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntryMedia>
 */
class JournalEntryMediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'media_type' => MediaType::Photo,
            'file_path' => 'journal/'.fake()->uuid().'.jpg',
            'sort_order' => 0,
        ];
    }
}
