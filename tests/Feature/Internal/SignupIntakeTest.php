<?php

namespace Tests\Feature\Internal;

use App\Models\LoginHandoff;
use App\Models\Market;
use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SignsIntakeRequests;
use Tests\TestCase;

class SignupIntakeTest extends TestCase
{
    use RefreshDatabase, SignsIntakeRequests;

    private const string PATH = '/api/internal/v1/signups';

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureIntake();
        Role::firstOrCreate(['name' => 'Org Admin', 'guard_name' => 'web']);
        PlatformSetting::forceCreate([
            'free_trial_enabled' => true,
            'default_trial_length_days' => 14,
            'trial_plan_entitlement' => 'pro',
        ]);
        Market::create([
            'code' => 'CA', 'name' => 'Canada', 'country_code' => 'CA',
            'currency' => 'CAD', 'annual_discount_rate' => 0.800,
            'tax_name' => 'GST', 'tax_rate' => 0.0500, 'tax_confirmed_at' => now(),
            'is_active' => true, 'is_fallback' => 1,
            'contract_version' => 'v1-cad-2026-07-23',
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'uuid' => $overrides['uuid'] ?? (string) Str::uuid(),
            'name' => 'Alex Chen',
            'email' => 'alex@cedarway.ca',
            // Cost must be ≤ the configured rounds (BCRYPT_ROUNDS=4 in
            // tests) or the endpoint's verifyConfiguration check rejects it.
            'password_hash' => password_hash('trial-password-2026', PASSWORD_BCRYPT, ['cost' => 4]),
            'country_code' => 'CA',
            'organization_name' => 'Cedar Way',
            'billing_timezone' => 'America/Vancouver',
            'verified_at' => now()->subMinute()->toIso8601String(),
            ...$overrides,
        ];
    }

    private function postSignup(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);

        return $this->postJson(self::PATH, $payload, $this->intakeHeaders('POST', self::PATH, $body));
    }

    public function test_valid_payload_provisions_the_whole_tenant(): void
    {
        $uuid = (string) Str::uuid();

        $response = $this->postSignup($this->payload(['uuid' => $uuid]))
            ->assertCreated()
            ->assertJsonStructure(['organization_id', 'user_id', 'handoff_token', 'handoff_expires_at']);

        $organization = Organization::where('name', 'Cedar Way')->sole();
        $this->assertSame($uuid, $organization->signup_reference);

        $user = User::where('email', 'alex@cedarway.ca')->sole();
        $this->assertTrue($user->hasRole('Org Admin'));
        $this->assertSame('organization', $user->access_level->value);

        $subscription = Subscription::sole();
        $this->assertTrue($subscription->is_trialing);
        $this->assertNull($subscription->plan_key);
        $this->assertSame('pro', $subscription->trial_plan_key->value);

        $handoff = LoginHandoff::sole();
        $this->assertSame('/organizations', $handoff->redirect_to);
        $this->assertSame(hash('sha256', $response->json('handoff_token')), $handoff->token_hash);
    }

    public function test_email_verified_at_is_the_payload_verification_time(): void
    {
        $verifiedAt = now()->subHours(2)->startOfSecond();

        $this->postSignup($this->payload(['verified_at' => $verifiedAt->toIso8601String()]))->assertCreated();

        $this->assertSame(
            $verifiedAt->utc()->format('Y-m-d H:i:s'),
            User::sole()->email_verified_at->utc()->format('Y-m-d H:i:s')
        );
    }

    public function test_handoff_ticket_lives_ten_minutes(): void
    {
        $this->freezeTime();
        $this->postSignup($this->payload())->assertCreated();

        $this->assertSame(
            now()->addMinutes(10)->format('Y-m-d H:i:s'),
            LoginHandoff::sole()->expires_at->format('Y-m-d H:i:s')
        );
    }

    public function test_same_uuid_is_idempotent_and_reissues_a_handoff(): void
    {
        $uuid = (string) Str::uuid();

        $first = $this->postSignup($this->payload(['uuid' => $uuid]))->assertCreated();
        $second = $this->postSignup($this->payload(['uuid' => $uuid]))->assertCreated();

        $this->assertSame(1, Organization::count());
        $this->assertSame(1, User::count());
        $this->assertSame($first->json('organization_id'), $second->json('organization_id'));
        $this->assertNotSame($first->json('handoff_token'), $second->json('handoff_token'));
        $this->assertSame(2, LoginHandoff::count());
    }

    public function test_existing_email_gets_409_and_the_existing_user_is_untouched(): void
    {
        $this->postSignup($this->payload())->assertCreated();
        $before = User::sole()->getAttributes();

        $this->postSignup($this->payload(['name' => 'Impostor', 'organization_name' => 'Evil Org']))
            ->assertStatus(409)
            ->assertExactJson(['error' => 'email_taken']);

        // Field-by-field: nothing about the existing user changed.
        $this->assertSame($before, User::sole()->getAttributes());
        $this->assertSame(1, Organization::count());
    }

    public function test_privileged_payload_fields_are_ignored(): void
    {
        $this->postSignup($this->payload([
            'plan_key' => 'pro',
            'lifecycle_status' => 'suspended',
            'is_test_account' => true,
            'access_level' => 'center',
            'type' => 'classroom_login',
            'id' => 999,
            'organization_id' => 999,
        ]))->assertCreated();

        $organization = Organization::sole();
        $user = User::sole();
        $this->assertSame('active', $organization->lifecycle_status->value);
        $this->assertFalse($organization->is_test_account);
        $this->assertSame('organization', $user->access_level->value);
        $this->assertSame('admin', $user->type->value);
        $this->assertNull(Subscription::sole()->plan_key);
    }

    public function test_non_bcrypt_password_hash_is_rejected_without_any_writes(): void
    {
        $this->postSignup($this->payload(['password_hash' => 'plaintext-password-123']))
            ->assertStatus(422)
            ->assertJsonPath('error', 'validation_failed');

        $this->assertSame(0, User::count());
        $this->assertSame(0, Organization::count());
    }

    public function test_timezone_outside_the_whitelist_is_rejected(): void
    {
        $this->postSignup($this->payload(['billing_timezone' => 'Europe/Paris']))
            ->assertStatus(422)
            ->assertJsonPath('error', 'validation_failed');
    }

    public function test_inactive_market_returns_503_with_reference_and_rolls_back(): void
    {
        Market::query()->update(['is_active' => false]);

        $response = $this->postSignup($this->payload())
            ->assertStatus(503)
            ->assertJsonPath('error', 'market_unavailable');

        $this->assertStringStartsWith('SGN-', $response->json('reference'));
        $this->assertSame(0, Organization::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, Subscription::count());
    }

    public function test_markets_endpoint_returns_exactly_six_fields_and_no_prices(): void
    {
        $path = '/api/internal/v1/markets';

        $response = $this->getJson($path, $this->intakeHeaders('GET', $path, '[]'))
            ->assertOk()
            ->assertJsonCount(1, 'markets');

        $this->assertSame(
            ['code', 'name', 'country_code', 'currency', 'is_active', 'is_fallback'],
            array_keys($response->json('markets.0'))
        );
        $this->assertStringNotContainsString('fee', $response->getContent());
        $this->assertStringNotContainsString('rate', $response->getContent());
    }

    public function test_responses_never_leak_existing_customer_data_or_traces(): void
    {
        User::factory()->create(['email' => 'existing@example.test', 'name' => 'Secret Customer']);

        $response = $this->postSignup($this->payload(['email' => 'existing@example.test']));

        $this->assertStringNotContainsString('Secret Customer', $response->getContent());
        $this->assertStringNotContainsString('Stack trace', $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }
}
