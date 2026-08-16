<?php

namespace Tests\Feature\Internal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\SignsIntakeRequests;
use Tests\TestCase;

class SignupIntakeAuthTest extends TestCase
{
    use RefreshDatabase, SignsIntakeRequests;

    private const string HEALTH = '/api/internal/v1/health';

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureIntake();
    }

    public function test_valid_signature_reaches_the_endpoint(): void
    {
        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_missing_or_wrong_signatures_get_a_uniform_401(): void
    {
        // No headers at all.
        $this->getJson(self::HEALTH)->assertStatus(401)->assertExactJson(['error' => 'unauthorized']);

        // Wrong signature.
        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]', ['signature' => str_repeat('0', 64)]))
            ->assertStatus(401)->assertExactJson(['error' => 'unauthorized']);

        // Unknown client.
        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]', ['client' => 'evil-1']))
            ->assertStatus(401)->assertExactJson(['error' => 'unauthorized']);

        // Signed with the wrong secret.
        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]', ['secret' => str_repeat('x', 64)]))
            ->assertStatus(401)->assertExactJson(['error' => 'unauthorized']);
    }

    public function test_timestamp_outside_the_60s_window_is_rejected(): void
    {
        $stale = (string) now()->subSeconds(61)->timestamp;

        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]', ['timestamp' => $stale]))
            ->assertStatus(401);
    }

    public function test_a_nonce_can_only_be_used_once(): void
    {
        $nonce = bin2hex(random_bytes(16));

        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]', ['nonce' => $nonce]))->assertOk();
        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]', ['nonce' => $nonce]))->assertStatus(401);
    }

    public function test_exceeding_the_hourly_quota_returns_429_and_logs_critical(): void
    {
        config(['services.signup_intake.rate_per_hour' => 2]);
        Log::spy();

        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]'))->assertOk();
        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]'))->assertOk();
        $this->getJson(self::HEALTH, $this->intakeHeaders('GET', self::HEALTH, '[]'))
            ->assertStatus(429)
            ->assertExactJson(['error' => 'rate_limited']);

        Log::shouldHaveReceived('critical')->once();
    }

    public function test_error_responses_never_leak_internals(): void
    {
        $response = $this->getJson(self::HEALTH);

        $this->assertStringNotContainsString('Stack trace', $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }
}
