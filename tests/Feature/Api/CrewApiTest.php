<?php

namespace Tests\Feature\Api;

use App\Enums\ChildGuardianType;
use App\Enums\GuardianRegistrationStatus;
use App\Models\Center;
use App\Models\Child;
use App\Models\Guardian;
use App\Notifications\GuardianInvite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CrewApiTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private Guardian $admin;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::factory()->create();
        $this->admin = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->child = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($this->admin, $this->child, [
            'is_account_admin' => true,
            'relationship' => 'Parent',
        ]);
    }

    public function test_the_crew_requires_a_token(): void
    {
        $this->getJson("/api/v1/children/{$this->child->id}/crew")->assertUnauthorized();
    }

    public function test_the_family_sees_the_crew_with_their_flags(): void
    {
        $granny = Guardian::factory()->create([
            'center_id' => $this->center->id,
            'first_name' => 'Anong',
            'last_name' => 'Chaiprasit',
        ]);
        $this->linkGuardianToChild($granny, $this->child, [
            'relationship' => 'Grandparent',
            'type' => ChildGuardianType::Guardian->value,
            'nickname' => 'Granny',
            'has_full_photo_access' => false,
        ]);

        $rows = collect($this->actingAsGuardian($this->admin)
            ->getJson("/api/v1/children/{$this->child->id}/crew")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->json('data'))
            ->keyBy('guardian_id');

        $this->assertTrue($rows[$this->admin->id]['is_account_admin']);
        $this->assertSame('Grandparent', $rows[$granny->id]['relationship']);
        $this->assertSame('Granny', $rows[$granny->id]['nickname']);
        $this->assertFalse($rows[$granny->id]['has_full_photo_access']);
        $this->assertSame('not_invited', $rows[$granny->id]['registration_status']);
    }

    public function test_another_familys_child_has_no_crew_to_see(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        $this->actingAsGuardian($this->admin)
            ->getJson("/api/v1/children/{$theirChild->id}/crew")
            ->assertNotFound();
    }

    public function test_an_admin_can_invite_a_new_crew_member(): void
    {
        Notification::fake();

        $this->actingAsGuardian($this->admin)
            ->postJson("/api/v1/children/{$this->child->id}/crew", [
                'email' => 'lucy@example.com',
                'name' => 'Lucy Lee Peng',
                'relationship' => 'Grandparent',
            ])
            ->assertCreated()
            ->assertJsonPath('data.0.name', 'Lucy Lee Peng')
            ->assertJsonPath('data.0.relationship', 'Grandparent')
            ->assertJsonPath('data.0.type', ChildGuardianType::Guardian->value)
            ->assertJsonPath('data.0.is_account_admin', false)
            ->assertJsonPath('data.0.has_full_photo_access', true)
            ->assertJsonPath('data.0.registration_status', 'invited');

        $lucy = Guardian::where('email', 'lucy@example.com')->sole();
        $this->assertSame('Lucy Lee', $lucy->first_name);
        $this->assertSame('Peng', $lucy->last_name);
        $this->assertSame($this->center->id, $lucy->center_id);
        $this->assertSame(GuardianRegistrationStatus::Invited, $lucy->registration_status);
        $this->assertNotNull($lucy->invited_at);

        Notification::assertSentTo($lucy, GuardianInvite::class);
    }

    public function test_a_parent_relationship_derives_the_parent_type(): void
    {
        Notification::fake();

        $this->actingAsGuardian($this->admin)
            ->postJson("/api/v1/children/{$this->child->id}/crew", [
                'email' => 'dad@example.com',
                'name' => 'Somchai Chaiprasit',
                'relationship' => 'Parent',
                'is_account_admin' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.0.type', ChildGuardianType::Parent->value)
            ->assertJsonPath('data.0.is_account_admin', true);
    }

    public function test_an_already_registered_guardian_is_linked_without_a_new_invite(): void
    {
        Notification::fake();

        $registered = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);

        $this->actingAsGuardian($this->admin)
            ->postJson("/api/v1/children/{$this->child->id}/crew", [
                'email' => $registered->email,
                'name' => 'Ignored For Existing',
                'relationship' => 'Friend',
            ])
            ->assertCreated();

        $this->assertTrue($registered->fresh()->ownsChild($this->child->id));
        // Their name is the center's record, not the inviter's spelling.
        $this->assertNotSame('Ignored', $registered->fresh()->first_name);
        Notification::assertNothingSent();
    }

    public function test_several_members_can_be_added_in_one_request(): void
    {
        Notification::fake();

        $this->actingAsGuardian($this->admin)
            ->postJson("/api/v1/children/{$this->child->id}/crew", [
                'members' => [
                    ['email' => 'a@example.com', 'name' => 'Aunt Amara', 'relationship' => 'Aunt/Uncle'],
                    ['email' => 'b@example.com', 'name' => 'Ben Osei', 'relationship' => 'Friend'],
                ],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data');

        $this->assertSame(3, $this->child->guardians()->count());
    }

    public function test_a_non_admin_cannot_write_to_the_crew(): void
    {
        $plain = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($plain, $this->child);

        $payload = ['email' => 'x@example.com', 'name' => 'X', 'relationship' => 'Friend'];

        $this->actingAsGuardian($plain)
            ->postJson("/api/v1/children/{$this->child->id}/crew", $payload)
            ->assertForbidden();
        $this->actingAsGuardian($plain)
            ->putJson("/api/v1/children/{$this->child->id}/crew/{$this->admin->id}", ['nickname' => 'Boss'])
            ->assertForbidden();
        $this->actingAsGuardian($plain)
            ->deleteJson("/api/v1/children/{$this->child->id}/crew/{$this->admin->id}")
            ->assertForbidden();

        // Reading stays open to the whole family.
        $this->actingAsGuardian($plain)
            ->getJson("/api/v1/children/{$this->child->id}/crew")
            ->assertOk();
    }

    public function test_an_admin_can_edit_a_members_flags_and_nickname(): void
    {
        $granny = Guardian::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($granny, $this->child, ['relationship' => 'Grandparent']);

        $this->actingAsGuardian($this->admin)
            ->putJson("/api/v1/children/{$this->child->id}/crew/{$granny->id}", [
                'nickname' => 'Granny',
                'relationship' => 'Guardian',
                'is_account_admin' => true,
                'has_full_photo_access' => false,
                'email' => 'granny@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.nickname', 'Granny')
            ->assertJsonPath('data.relationship', 'Guardian')
            ->assertJsonPath('data.type', ChildGuardianType::Parent->value)
            ->assertJsonPath('data.is_account_admin', true)
            ->assertJsonPath('data.has_full_photo_access', false)
            ->assertJsonPath('data.email', 'granny@example.com');
    }

    public function test_editing_someone_outside_the_crew_is_a_404(): void
    {
        $unrelated = Guardian::factory()->create(['center_id' => $this->center->id]);

        $this->actingAsGuardian($this->admin)
            ->putJson("/api/v1/children/{$this->child->id}/crew/{$unrelated->id}", ['nickname' => 'X'])
            ->assertNotFound();
    }

    public function test_removing_a_member_detaches_the_pivot_but_keeps_the_guardian(): void
    {
        $granny = Guardian::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($granny, $this->child, ['relationship' => 'Grandparent']);

        $this->actingAsGuardian($this->admin)
            ->deleteJson("/api/v1/children/{$this->child->id}/crew/{$granny->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('child_guardian', [
            'child_id' => $this->child->id,
            'guardian_id' => $granny->id,
        ]);
        $this->assertDatabaseHas('guardians', ['id' => $granny->id]);
    }

    public function test_the_last_account_admin_cannot_be_removed(): void
    {
        $this->actingAsGuardian($this->admin)
            ->deleteJson("/api/v1/children/{$this->child->id}/crew/{$this->admin->id}")
            ->assertStatus(422);

        $this->assertTrue($this->admin->fresh()->ownsChild($this->child->id));

        // With a second admin in place, the first may leave.
        $other = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($other, $this->child, ['is_account_admin' => true]);

        $this->actingAsGuardian($this->admin)
            ->deleteJson("/api/v1/children/{$this->child->id}/crew/{$this->admin->id}")
            ->assertNoContent();
    }
}
