<?php

namespace Database\Seeders;

use App\Models\MilestoneDefinition;
use Illuminate\Database\Seeder;

/**
 * The global milestone lists (API_08), verbatim from the wireframe screens.
 * Prenatal, and preschool cognitive/language/social, were not captured in
 * the source screenshots and stay empty on purpose — do not fill them in to
 * look complete.
 */
class MilestoneDefinitionSeeder extends Seeder
{
    private const array DEFINITIONS = [
        'infant' => [
            'firsts' => ['First Bath', 'First Outing', 'First Tooth', 'First Word'],
            'physical' => ['Holding Head Steady', 'Picking Up Objects', 'Sitting Without Help', 'Rolling Over', 'Crawling'],
            'cognitive' => ['Recognizing People', 'Following Objects With Eyes', 'Reaching For Toys', 'Bringing Things To Mouth', 'Playing Peekaboo'],
            'language' => ['Babbling', 'Turning Head Towards Sound', 'Responding To Own Name', 'Understanding "No"', 'Pointing'],
            'social' => ['Looking At Parent', 'Smiling', 'Laughing', 'Waving Goodbye', 'Imitating Adult Actions'],
        ],
        'toddler' => [
            'firsts' => ['First Hair Cut', 'First Holiday', 'First Step', 'First Play Date'],
            'physical' => ['Standing Without Help', 'Walking', 'Scribbling', 'Running', 'Toilet Training'],
            'cognitive' => ['Hiding Objects', 'Sharing', 'Cleaning Up', 'Following Simple Directions', 'Copying Gestures'],
            'language' => ['Imitating Sounds', 'Saying Two Word Phrase', 'Enjoying Sounds', 'Giving Directions', 'Asking Directions'],
            'social' => ['Playing Alone', 'Hugging & Kissing', 'Having A Temper Tantrum', 'Being Bossy', 'Hoarding Toys'],
        ],
        'preschool' => [
            'firsts' => ['First Dentist Visit', 'First Best Friend', 'First Birthday Party'],
            'physical' => ['First Train Ride', 'Jumping On The Spot', 'Tricycling', 'Throwing Ball', 'Climbing', 'Brushing Own Teeth'],
        ],
        'school' => [
            'firsts' => ['First Plane Ride', 'First Time At Cinema', 'First Day At School', 'First Bike'],
            'physical' => ['Somersault', 'Walking Balance Beam', 'Skipping', 'Catching Ball', 'Bicycling'],
            'cognitive' => ['Reading The Clock', 'Counting And Saving Money', 'Reciting The Alphabet', 'Tying Laces', 'Printing Letters And Numbers'],
            'language' => ['Answering Telephone', 'Knowing Basic Colors', 'Identifying Right From Left', 'Telling Stories', 'Joking'],
            'social' => ['Dressing Self', 'Measuring Against Others', 'Preferring To Play With Friends', 'Befriending Opposite Gender', 'Helping With Chores'],
        ],
    ];

    public function run(): void
    {
        foreach (self::DEFINITIONS as $ageGroup => $categories) {
            foreach ($categories as $category => $names) {
                foreach ($names as $index => $name) {
                    MilestoneDefinition::firstOrCreate([
                        'center_id' => null,
                        'child_id' => null,
                        'age_group' => $ageGroup,
                        'category' => $category,
                        'name' => $name,
                    ], [
                        'sort_order' => $index,
                        'is_custom' => false,
                    ]);
                }
            }
        }
    }
}
