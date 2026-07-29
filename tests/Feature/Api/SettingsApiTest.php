<?php

namespace Tests\Feature\Api;

use App\Models\Center;
use App\Models\Guardian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private Center $center;

    private Guardian $guardian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->center = Center::factory()->create();
        $this->guardian = Guardian::factory()->registered()->create([
            'center_id' => $this->center->id,
            'first_name' => 'Hao',
            'last_name' => 'Feng',
            'email' => 'hao@example.com',
        ]);
    }

    public function test_settings_require_a_token(): void
    {
        $this->getJson('/api/v1/settings')->assertUnauthorized();
    }

    public function test_the_settings_screen_gets_all_three_blocks_with_defaults(): void
    {
        $this->assertDatabaseCount('guardian_notification_preferences', 0);

        $this->actingAsGuardian($this->guardian)
            ->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('profile.name', 'Hao Feng')
            ->assertJsonPath('profile.username', 'hao@example.com')
            ->assertJsonPath('email.email', 'hao@example.com')
            ->assertJsonPath('email.receive_fewer_emails', false)
            ->assertJsonPath('email.email_language', 'en')
            ->assertJsonPath('notifications.report_started', true)
            ->assertJsonPath('notifications.classroom_photos', true);

        // First read created the default row.
        $this->assertDatabaseHas('guardian_notification_preferences', [
            'guardian_id' => $this->guardian->id,
        ]);
    }

    public function test_the_profile_name_is_split_on_the_last_word(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/profile', ['name' => 'Lucy Lee Peng'])
            ->assertOk()
            ->assertJsonPath('profile.name', 'Lucy Lee Peng');

        $fresh = $this->guardian->fresh();
        $this->assertSame('Lucy Lee', $fresh->first_name);
        $this->assertSame('Peng', $fresh->last_name);
    }

    public function test_changing_email_requires_a_matching_retype(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/email', [
                'email' => 'new@example.com',
                'email_confirmation' => 'other@example.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/email', [
                'email' => 'new@example.com',
                'email_confirmation' => 'new@example.com',
                'receive_fewer_emails' => true,
                'email_language' => 'zh-TW',
            ])
            ->assertOk()
            ->assertJsonPath('email.email', 'new@example.com')
            ->assertJsonPath('email.receive_fewer_emails', true)
            ->assertJsonPath('email.email_language', 'zh-TW');

        $this->assertSame('new@example.com', $this->guardian->fresh()->email);
    }

    public function test_the_new_email_may_not_belong_to_another_guardian_at_the_center(): void
    {
        Guardian::factory()->create([
            'center_id' => $this->center->id,
            'email' => 'taken@example.com',
        ]);

        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/email', [
                'email' => 'taken@example.com',
                'email_confirmation' => 'taken@example.com',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // The same address at ANOTHER center is fine — one parent, two
        // centers, two guardian rows is a supported shape (API_03).
        Guardian::factory()->create(['email' => 'elsewhere@example.com']);

        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/email', [
                'email' => 'elsewhere@example.com',
                'email_confirmation' => 'elsewhere@example.com',
            ])
            ->assertOk();
    }

    public function test_the_email_language_is_validated(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/email', [
                'email' => 'hao@example.com',
                'email_confirmation' => 'hao@example.com',
                'email_language' => 'fr',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email_language']);
    }

    public function test_changing_the_password_verifies_the_current_one(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/password', [
                'current_password' => 'correct-horse-battery',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/password', [
                'current_password' => 'correct-horse-battery',
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-secret-password', $this->guardian->fresh()->password));
    }

    public function test_notification_toggles_persist_in_the_guardian_table_only(): void
    {
        $this->actingAsGuardian($this->guardian)
            ->putJson('/api/v1/settings/notifications', [
                'report_started' => false,
                'new_photo' => false,
            ])
            ->assertOk()
            ->assertJsonPath('notifications.report_started', false)
            ->assertJsonPath('notifications.new_photo', false)
            ->assertJsonPath('notifications.report_ready', true);

        $this->assertDatabaseHas('guardian_notification_preferences', [
            'guardian_id' => $this->guardian->id,
            'report_started' => false,
            'new_photo' => false,
            'report_ready' => true,
        ]);
        // The center-side staff table stays untouched.
        $this->assertDatabaseCount('notification_preferences', 0);
    }
}
