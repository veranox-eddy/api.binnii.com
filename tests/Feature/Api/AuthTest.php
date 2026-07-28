<?php

namespace Tests\Feature\Api;

use App\Enums\EnrollmentStatus;
use App\Enums\GuardianRegistrationStatus;
use App\Models\Center;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\User;
use App\Notifications\GuardianResetPassword;
use App\Support\GuardianActivationToken;
use Database\Factories\GuardianFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_validate_returns_the_guardian_behind_a_valid_token(): void
    {
        $guardian = Guardian::factory()->invited()->create([
            'first_name' => 'Hao',
            'last_name' => 'Feng',
            'email' => 'hao@example.com',
        ]);

        $this->postJson('/api/v1/auth/activation/validate', [
            'token' => GuardianActivationToken::for($guardian),
        ])
            ->assertOk()
            ->assertExactJson([
                'guardian' => [
                    'first_name' => 'Hao',
                    'last_name' => 'Feng',
                    'email' => 'hao@example.com',
                ],
                'min_password_length' => 12,
            ]);
    }

    public function test_activation_validate_rejects_a_tampered_token(): void
    {
        $guardian = Guardian::factory()->invited()->create();
        $token = GuardianActivationToken::for($guardian);

        $this->postJson('/api/v1/auth/activation/validate', ['token' => $token.'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');
    }

    public function test_activation_validate_rejects_an_expired_token(): void
    {
        $guardian = Guardian::factory()->invited()->create();

        $token = $this->travel(-30)->days(fn () => GuardianActivationToken::for($guardian));

        $this->postJson('/api/v1/auth/activation/validate', ['token' => $token])
            ->assertStatus(422)
            ->assertJsonValidationErrors('token');
    }

    public function test_activation_complete_sets_the_password_and_issues_a_token(): void
    {
        $guardian = Guardian::factory()->invited()->create();

        $response = $this->postJson('/api/v1/auth/activation/complete', [
            'token' => GuardianActivationToken::for($guardian),
            'name' => 'Lucy Lee Peng',
            'password' => 'sunflower-meadow-42',
            'password_confirmation' => 'sunflower-meadow-42',
        ])->assertCreated();

        $response->assertJsonStructure(['token', 'token_type', 'expires_in', 'guardian' => ['id', 'name', 'email']]);
        $this->assertSame('bearer', $response->json('token_type'));

        $guardian->refresh();
        $this->assertSame('Lucy Lee', $guardian->first_name);
        $this->assertSame('Peng', $guardian->last_name);
        $this->assertSame(GuardianRegistrationStatus::Registered, $guardian->registration_status);
        $this->assertNotNull($guardian->email_verified_at);
        $this->assertTrue(Hash::check('sunflower-meadow-42', $guardian->password));

        // The freshly minted token has to actually work on a private route.
        $this->withToken($response->json('token'))->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_an_activation_token_cannot_be_used_twice(): void
    {
        $guardian = Guardian::factory()->invited()->create();
        $token = GuardianActivationToken::for($guardian);

        $payload = [
            'token' => $token,
            'name' => 'Osman',
            'password' => 'sunflower-meadow-42',
            'password_confirmation' => 'sunflower-meadow-42',
        ];

        $this->postJson('/api/v1/auth/activation/complete', $payload)->assertCreated();

        $this->postJson('/api/v1/auth/activation/complete', [
            ...$payload,
            'password' => 'a-different-password',
            'password_confirmation' => 'a-different-password',
        ])->assertStatus(422)->assertJsonValidationErrors('token');

        // …and the second attempt left the first password untouched.
        $this->assertTrue(Hash::check('sunflower-meadow-42', $guardian->refresh()->password));
    }

    public function test_activation_enforces_a_twelve_character_password(): void
    {
        $guardian = Guardian::factory()->invited()->create();

        $this->postJson('/api/v1/auth/activation/complete', [
            'token' => GuardianActivationToken::for($guardian),
            'name' => 'Osman',
            'password' => 'elevenchars',
            'password_confirmation' => 'elevenchars',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertNull($guardian->refresh()->password);
    }

    public function test_login_returns_a_token_for_correct_credentials(): void
    {
        $guardian = Guardian::factory()->registered()->create(['email' => 'hao@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'hao@example.com',
            'password' => GuardianFactory::PASSWORD,
        ])->assertOk();

        $this->assertSame('bearer', $response->json('token_type'));
        $this->assertSame(3600, $response->json('expires_in'));
        $this->assertSame($guardian->id, $response->json('guardian.id'));
        $this->assertNotNull($guardian->refresh()->last_login_at);
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        Guardian::factory()->registered()->create(['email' => 'hao@example.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'hao@example.com',
            'password' => 'not-the-password',
        ])->assertUnauthorized()->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_rejects_a_guardian_who_has_not_activated(): void
    {
        // Password set but never activated: the status alone must block them.
        Guardian::factory()->invited()->create([
            'email' => 'hao@example.com',
            'password' => GuardianFactory::PASSWORD,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'hao@example.com',
            'password' => GuardianFactory::PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_login_rejects_a_registered_guardian_with_no_password(): void
    {
        // Hash::check against a null password must not be what stops this.
        Guardian::factory()->create([
            'email' => 'hao@example.com',
            'password' => null,
            'registration_status' => GuardianRegistrationStatus::Registered,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'hao@example.com',
            'password' => GuardianFactory::PASSWORD,
        ])->assertUnauthorized();
    }

    public function test_staff_credentials_are_not_accepted(): void
    {
        User::factory()->create([
            'email' => 'teacher@example.com',
            'password' => 'a-staff-password-1',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'teacher@example.com',
            'password' => 'a-staff-password-1',
        ])->assertUnauthorized();
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_only_this_guardians_children(): void
    {
        $center = Center::factory()->create(['name' => 'Sunny Days', 'timezone' => 'Asia/Taipei']);
        $classroom = Classroom::factory()->create(['center_id' => $center->id, 'name' => 'Infant Room']);

        $guardian = Guardian::factory()->registered()->create(['center_id' => $center->id]);
        $mine = Child::factory()->create(['center_id' => $center->id, 'first_name' => 'Anderson', 'last_name' => 'Feng']);
        Enrollment::factory()->create([
            'child_id' => $mine->id,
            'classroom_id' => $classroom->id,
            'status' => EnrollmentStatus::Active,
        ]);
        $this->linkGuardianToChild($guardian, $mine, ['is_account_admin' => true]);

        // Another family at the same center, and a child at another center.
        $siblingOfSomeoneElse = Child::factory()->create(['center_id' => $center->id]);
        $otherGuardian = Guardian::factory()->registered()->create(['center_id' => $center->id]);
        $this->linkGuardianToChild($otherGuardian, $siblingOfSomeoneElse);
        Child::factory()->create();

        $response = $this->actingAsGuardian($guardian)->getJson('/api/v1/auth/me')->assertOk();

        $response->assertJsonCount(1, 'children');
        $response->assertJsonPath('guardian.id', $guardian->id);
        $response->assertJsonPath('children.0.id', $mine->id);
        $response->assertJsonPath('children.0.display_name', 'Anderson Feng');
        $response->assertJsonPath('children.0.classroom.name', 'Infant Room');
        $response->assertJsonPath('children.0.access.is_account_admin', true);
        $response->assertJsonPath('children.0.access.has_full_photo_access', true);
        $response->assertJsonPath('center.id', $center->id);
        $response->assertJsonPath('center.settings.child_name_display', 'full_last');
    }

    public function test_refresh_rotates_the_token_and_retires_the_old_one(): void
    {
        $guardian = Guardian::factory()->registered()->create();
        $original = $this->postJson('/api/v1/auth/login', [
            'email' => $guardian->email,
            'password' => GuardianFactory::PASSWORD,
        ])->json('token');

        $rotated = $this->withToken($original)->postJson('/api/v1/auth/refresh')->assertOk()->json('token');

        $this->assertNotSame($original, $rotated);
        $this->withToken($rotated)->getJson('/api/v1/auth/me')->assertOk();
        $this->withToken($original)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_logout_blacklists_the_current_token(): void
    {
        $guardian = Guardian::factory()->registered()->create();
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $guardian->email,
            'password' => GuardianFactory::PASSWORD,
        ])->json('token');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_forgot_password_emails_an_activated_guardian(): void
    {
        Notification::fake();
        $guardian = Guardian::factory()->registered()->create(['email' => 'hao@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'hao@example.com'])->assertOk();

        Notification::assertSentTo($guardian, GuardianResetPassword::class);
    }

    public function test_forgot_password_says_nothing_about_unknown_or_inactive_accounts(): void
    {
        Notification::fake();
        $invited = Guardian::factory()->invited()->create(['email' => 'pending@example.com']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'pending@example.com'])->assertOk();
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

        Notification::assertNothingSentTo($invited);
    }

    public function test_reset_password_sets_a_new_password(): void
    {
        $guardian = Guardian::factory()->registered()->create(['email' => 'hao@example.com']);
        $token = Password::broker('guardians')->createToken($guardian);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'hao@example.com',
            'password' => 'a-brand-new-passphrase',
            'password_confirmation' => 'a-brand-new-passphrase',
        ])->assertOk();

        $this->assertTrue(Hash::check('a-brand-new-passphrase', $guardian->refresh()->password));

        $this->postJson('/api/v1/auth/login', [
            'email' => 'hao@example.com',
            'password' => 'a-brand-new-passphrase',
        ])->assertOk();
    }

    public function test_reset_password_rejects_a_bad_token_and_a_short_password(): void
    {
        $guardian = Guardian::factory()->registered()->create(['email' => 'hao@example.com']);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'hao@example.com',
            'password' => 'a-brand-new-passphrase',
            'password_confirmation' => 'a-brand-new-passphrase',
        ])->assertStatus(422)->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => Password::broker('guardians')->createToken($guardian),
            'email' => 'hao@example.com',
            'password' => 'elevenchars',
            'password_confirmation' => 'elevenchars',
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertTrue(Hash::check(GuardianFactory::PASSWORD, $guardian->refresh()->password));
    }
}
