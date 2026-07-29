<?php

namespace Tests\Feature\Api;

use App\Models\Center;
use App\Models\Child;
use App\Models\Guardian;
use App\Models\MilestoneDefinition;
use Database\Seeders\MilestoneDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestonesApiTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private Guardian $guardian;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MilestoneDefinitionSeeder::class);

        $this->center = Center::factory()->create();
        $this->guardian = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->child = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($this->guardian, $this->child);
    }

    public function test_milestones_require_a_token(): void
    {
        $this->getJson("/api/v1/children/{$this->child->id}/milestones")->assertUnauthorized();
    }

    public function test_another_guardians_child_has_no_milestones_to_see(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$theirChild->id}/milestones")
            ->assertNotFound();
    }

    public function test_the_infant_group_is_the_default_and_matches_the_wireframe_verbatim(): void
    {
        $response = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/milestones")
            ->assertOk()
            ->assertJsonPath('age_group', 'infant');

        $categories = $response->json('categories');

        // The five categories arrive in display order, even when empty.
        $this->assertSame(['firsts', 'physical', 'cognitive', 'language', 'social'], array_keys($categories));

        $this->assertSame(
            ['First Bath', 'First Outing', 'First Tooth', 'First Word'],
            array_column($categories['firsts'], 'name'),
        );
        // Spot-checks the spec calls out.
        $this->assertContains('Understanding "No"', array_column($categories['language'], 'name'));

        $this->assertNull($categories['firsts'][0]['achieved_on']);
        $this->assertFalse($categories['firsts'][0]['is_custom']);
    }

    public function test_uncaptured_groups_are_empty_not_faked(): void
    {
        $prenatal = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/milestones?age_group=prenatal")
            ->assertOk()
            ->json('categories');

        $this->assertSame([], collect($prenatal)->flatten(1)->all());

        $preschool = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/milestones?age_group=preschool")
            ->assertOk()
            ->json('categories');

        $this->assertNotEmpty($preschool['firsts']);
        $this->assertSame([], $preschool['cognitive']);
        $this->assertSame([], $preschool['language']);
        $this->assertSame([], $preschool['social']);

        $toddler = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/milestones?age_group=toddler")
            ->assertOk()
            ->json('categories');

        $this->assertContains('Hugging & Kissing', array_column($toddler['social'], 'name'));
    }

    public function test_submitting_milestones_upserts_and_records_the_guardian(): void
    {
        $firstBath = MilestoneDefinition::where('name', 'First Bath')->sole();
        $crawling = MilestoneDefinition::where('name', 'Crawling')->sole();

        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/children/{$this->child->id}/milestones", [
                'items' => [
                    ['milestone_definition_id' => $firstBath->id, 'achieved_on' => '2026-05-01', 'description' => 'Loved it'],
                    ['milestone_definition_id' => $crawling->id, 'achieved_on' => null, 'description' => null],
                ],
            ])
            ->assertOk()
            ->assertJson(['saved' => 2]);

        $this->assertDatabaseHas('child_milestones', [
            'child_id' => $this->child->id,
            'milestone_definition_id' => $firstBath->id,
            'recorded_by_guardian_id' => $this->guardian->id,
            'description' => 'Loved it',
        ]);

        // Submitting again updates in place — the unique key holds.
        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/children/{$this->child->id}/milestones", [
                'items' => [
                    ['milestone_definition_id' => $firstBath->id, 'achieved_on' => '2026-06-15', 'description' => 'Corrected date'],
                ],
            ])
            ->assertOk();

        $this->assertSame(1, $this->child->milestones()->where('milestone_definition_id', $firstBath->id)->count());
        $this->assertDatabaseHas('child_milestones', [
            'milestone_definition_id' => $firstBath->id,
            'description' => 'Corrected date',
        ]);

        // ...and the achievement shows up in the GET merge.
        $firsts = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/milestones")
            ->json('categories.firsts');

        $this->assertSame('2026-06-15', collect($firsts)->firstWhere('definition_id', $firstBath->id)['achieved_on']);
    }

    public function test_a_custom_milestone_is_created_and_listed_for_that_child_only(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->postJson("/api/v1/children/{$this->child->id}/milestones/custom", [
                'age_group' => 'infant',
                'category' => 'firsts',
                'name' => 'First Ferry Ride',
                'achieved_on' => '2026-07-01',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'First Ferry Ride')
            ->assertJsonPath('data.is_custom', true)
            ->assertJsonPath('data.achieved_on', '2026-07-01');

        $names = array_column($this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/milestones")
            ->json('categories.firsts'), 'name');

        $this->assertContains('First Ferry Ride', $names);

        // A sibling in the same family does not inherit it.
        $sibling = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($this->guardian, $sibling);

        $siblingNames = array_column($this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$sibling->id}/milestones")
            ->json('categories.firsts'), 'name');

        $this->assertNotContains('First Ferry Ride', $siblingNames);
    }

    public function test_another_childs_custom_definition_cannot_be_submitted(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        $theirs = MilestoneDefinition::create([
            'child_id' => $theirChild->id,
            'age_group' => 'infant',
            'category' => 'firsts',
            'name' => 'Their Private First',
            'sort_order' => 0,
            'is_custom' => true,
        ]);

        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/children/{$this->child->id}/milestones", [
                'items' => [
                    ['milestone_definition_id' => $theirs->id, 'achieved_on' => '2026-05-01'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_the_age_group_is_validated(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}/milestones?age_group=teen")
            ->assertStatus(422);
    }
}
