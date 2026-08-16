<?php

namespace Tests\Feature\Internal;

use App\Models\Market;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\SignsIntakeRequests;
use Tests\TestCase;

/**
 * Contract guard (staged-registration spec §12.7): the fixtures under
 * tests/fixtures/signup-intake exist IDENTICALLY in this repo and in
 * signup.binnii.com. This side asserts the endpoint speaks them; the
 * signup side asserts its worker does. Changing a field on one side
 * without syncing the fixture turns exactly one suite red.
 */
class SignupIntakeContractTest extends TestCase
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
    private function fixture(string $name): array
    {
        return json_decode(file_get_contents(base_path("tests/fixtures/signup-intake/{$name}.json")), true);
    }

    private function postFixture(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);

        return $this->postJson(self::PATH, $payload, $this->intakeHeaders('POST', self::PATH, $body));
    }

    public function test_the_request_fixture_provisions_and_the_201_shape_matches(): void
    {
        $response = $this->postFixture($this->fixture('request'))->assertCreated();

        // Same key set as the committed 201 fixture (values are dynamic).
        $this->assertSame(
            array_keys($this->fixture('response-201')),
            array_keys($response->json())
        );
        $this->assertSame(64, strlen($response->json('handoff_token')));
    }

    public function test_the_409_shape_matches(): void
    {
        $request = $this->fixture('request');
        User::factory()->create(['email' => $request['email']]);

        $response = $this->postFixture($request)->assertStatus(409);

        $this->assertSame(array_keys($this->fixture('response-409')), array_keys($response->json()));
        $this->assertSame('email_taken', $response->json('error'));
    }

    public function test_the_422_shape_matches(): void
    {
        $response = $this->postFixture([
            ...$this->fixture('request'),
            'billing_timezone' => 'Europe/Paris',
        ])->assertStatus(422);

        $this->assertSame(array_keys($this->fixture('response-422')), array_keys($response->json()));
        $this->assertArrayHasKey('billing_timezone', $response->json('fields'));
    }

    public function test_the_503_shape_matches(): void
    {
        Market::query()->update(['is_active' => false]);

        $response = $this->postFixture($this->fixture('request'))->assertStatus(503);

        $this->assertSame(array_keys($this->fixture('response-503')), array_keys($response->json()));
        $this->assertStringStartsWith('SGN-', $response->json('reference'));
    }

    public function test_the_fixtures_are_byte_identical_across_repos(): void
    {
        // Belt and braces for the same-machine phase: as long as both repos
        // are checked out side by side, catch an unsynced fixture directly.
        $sibling = dirname(base_path()).'/signup.binnii.com/tests/fixtures/signup-intake';

        if (! is_dir($sibling)) {
            $this->markTestSkipped('signup.binnii.com is not checked out next to this repo.');
        }

        foreach (glob(base_path('tests/fixtures/signup-intake/*.json')) as $file) {
            $this->assertSame(
                file_get_contents($file),
                file_get_contents($sibling.'/'.basename($file)),
                basename($file).' differs between the two repos.'
            );
        }
    }
}
