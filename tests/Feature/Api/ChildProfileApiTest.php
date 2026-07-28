<?php

namespace Tests\Feature\Api;

use App\Enums\ChildGender;
use App\Enums\ChildNameDisplay;
use App\Enums\EnrollmentStatus;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Guardian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChildProfileApiTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private Guardian $guardian;

    private Child $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::factory()->create(['timezone' => 'America/Vancouver']);
        $this->guardian = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->child = Child::factory()->create([
            'center_id' => $this->center->id,
            'first_name' => 'Anderson',
            'last_name' => 'Feng',
            'date_of_birth' => '2022-07-21',
            'gender' => ChildGender::Boy,
        ]);
        $this->linkGuardianToChild($this->guardian, $this->child, [
            'is_account_admin' => true,
            'relationship' => 'Parent',
        ]);
    }

    public function test_index_requires_a_token(): void
    {
        $this->getJson('/api/v1/children')->assertUnauthorized();
    }

    public function test_index_lists_only_the_guardians_own_children(): void
    {
        $classroom = Classroom::factory()->create(['center_id' => $this->center->id, 'name' => 'Infant Room']);
        Enrollment::factory()->create([
            'child_id' => $this->child->id,
            'classroom_id' => $classroom->id,
            'status' => EnrollmentStatus::Active,
        ]);

        // Another family at the same center, plus a child elsewhere.
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, Child::factory()->create(['center_id' => $this->center->id]));
        Child::factory()->create();

        $response = $this->actingAsGuardian($this->guardian)->getJson('/api/v1/children')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->child->id);
        $response->assertJsonPath('data.0.display_name', 'Anderson Feng');
        $response->assertJsonPath('data.0.classroom.name', 'Infant Room');
        $response->assertJsonPath('data.0.access.is_account_admin', true);
    }

    public function test_show_returns_the_profile_with_this_guardians_pivot(): void
    {
        $response = $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}")
            ->assertOk();

        $response->assertJsonPath('data.first_name', 'Anderson');
        $response->assertJsonPath('data.date_of_birth', '2022-07-21');
        $response->assertJsonPath('data.birthday_formatted', 'Jul 21, 2022');
        $response->assertJsonPath('data.gender', 'boy');
        $response->assertJsonPath('data.my_relationship.relationship', 'Parent');
        $response->assertJsonPath('data.my_relationship.type', 'parent');
        $response->assertJsonPath('data.my_relationship.nickname', null);
        $this->assertMatchesRegularExpression('/^\d+y( \d+m)?$/', $response->json('data.age_string'));
    }

    public function test_display_name_honours_the_centers_name_setting(): void
    {
        CenterSetting::create([
            'center_id' => $this->center->id,
            'child_name_display' => ChildNameDisplay::LastInitial,
        ]);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$this->child->id}")
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Anderson F.')
            // The raw parts stay intact — this is presentation, not redaction.
            ->assertJsonPath('data.last_name', 'Feng');
    }

    public function test_another_guardians_child_is_not_found(): void
    {
        $stranger = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $theirChild = Child::factory()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($stranger, $theirChild);

        $this->actingAsGuardian($this->guardian)
            ->getJson("/api/v1/children/{$theirChild->id}")
            ->assertNotFound();

        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/children/{$theirChild->id}", [
                'first_name' => 'Hijacked',
                'last_name' => 'Feng',
                'birthday' => '2022-07-21',
                'gender' => 'boy',
            ])
            ->assertNotFound();

        $this->assertNotSame('Hijacked', $theirChild->refresh()->first_name);
    }

    public function test_update_writes_identity_and_only_this_guardians_pivot(): void
    {
        $coParent = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($coParent, $this->child, [
            'relationship' => 'Guardian',
            'nickname' => 'Papa',
        ]);

        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/children/{$this->child->id}", [
                'first_name' => 'Andy',
                'last_name' => 'Feng',
                'birthday' => '2022-08-01',
                'gender' => 'x',
                'relationship' => 'Caregiver',
                'nickname' => 'Mama',
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Andy')
            ->assertJsonPath('data.date_of_birth', '2022-08-01')
            ->assertJsonPath('data.gender', 'x')
            ->assertJsonPath('data.my_relationship.relationship', 'Caregiver')
            ->assertJsonPath('data.my_relationship.nickname', 'Mama');

        $this->assertDatabaseHas('child_guardian', [
            'child_id' => $this->child->id,
            'guardian_id' => $coParent->id,
            'relationship' => 'Guardian',
            'nickname' => 'Papa',
        ]);
    }

    public function test_a_future_birthday_is_accepted_for_an_expected_child(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/children/{$this->child->id}", [
                'first_name' => 'Anderson',
                'last_name' => 'Feng',
                'birthday' => now()->addMonths(3)->toDateString(),
                'gender' => 'boy',
            ])
            ->assertOk();
    }

    public function test_a_non_admin_guardian_may_edit_their_pivot_but_not_the_child(): void
    {
        $coParent = Guardian::factory()->registered()->create(['center_id' => $this->center->id]);
        $this->linkGuardianToChild($coParent, $this->child, ['is_account_admin' => false]);

        // Same identity values, different pivot: allowed.
        $this->actingAsGuardian($coParent)
            ->putJson("/api/v1/children/{$this->child->id}", [
                'first_name' => 'Anderson',
                'last_name' => 'Feng',
                'birthday' => '2022-07-21',
                'gender' => 'boy',
                'nickname' => 'Bubba',
            ])
            ->assertOk()
            ->assertJsonPath('data.my_relationship.nickname', 'Bubba');

        // Changing the child itself: refused.
        $this->actingAsGuardian($coParent)
            ->putJson("/api/v1/children/{$this->child->id}", [
                'first_name' => 'Renamed',
                'last_name' => 'Feng',
                'birthday' => '2022-07-21',
                'gender' => 'boy',
            ])
            ->assertForbidden();

        $this->assertSame('Anderson', $this->child->refresh()->first_name);
    }

    public function test_a_photo_upload_is_stored_and_returned(): void
    {
        Storage::fake('public');

        $response = $this->actingAsGuardian($this->guardian)
            ->put("/api/v1/children/{$this->child->id}", [
                'first_name' => 'Anderson',
                'last_name' => 'Feng',
                'birthday' => '2022-07-21',
                'gender' => 'boy',
                'photo' => UploadedFile::fake()->image('anderson.jpg'),
            ])
            ->assertOk();

        $path = $this->child->refresh()->photo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString($path, $response->json('data.photo_url'));
    }

    public function test_update_validates_its_input(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/children/{$this->child->id}", [
                'first_name' => '',
                'gender' => 'alien',
                'relationship' => 'Landlord',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'birthday', 'gender', 'relationship']);
    }

    public function test_update_accepts_the_three_part_date_picker(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->putJson("/api/v1/children/{$this->child->id}", [
                'first_name' => 'Anderson',
                'last_name' => 'Feng',
                'birth_year' => '2021',
                'birth_month' => '3',
                'birth_day' => '9',
                'gender' => 'boy',
            ])
            ->assertOk()
            ->assertJsonPath('data.date_of_birth', '2021-03-09');
    }
}
