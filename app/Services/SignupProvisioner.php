<?php

namespace App\Services;

use App\Enums\AccessLevel;
use App\Enums\OrganizationLifecycleStatus;
use App\Enums\PaymentMethodReadiness;
use App\Enums\UserType;
use App\Exceptions\EmailTakenException;
use App\Models\LoginHandoff;
use App\Models\Organization;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Provisions a verified signup in ONE transaction
 * (specs/saas/build-signup-staged-registration.md §6.5).
 *
 * HARD RULES (tested, not just documented):
 * - This service NEVER updates an existing organization / user /
 *   subscription — INSERT only. An idempotency hit (signup_reference
 *   already present) means "skip and re-issue a handoff", never "update".
 * - plan_key / lifecycle_status / is_test_account / access_level / type /
 *   any id are decided HERE, never accepted from the payload.
 * - Nothing about existing customers is ever returned.
 *
 * Deviations from the main spec, by design: login_handoffs TTL is 10
 * minutes (the ticket is produced by a background worker, the user may be
 * seconds-to-minutes behind) — still single-use, user-bound, relative
 * redirect only. users.email_verified_at is the payload's verification
 * time, set at INSERT (unverified users never exist in MySQL).
 */
class SignupProvisioner
{
    public function __construct(private MarketResolver $markets) {}

    /**
     * @param  array{uuid: string, name: string, email: string, password_hash: string, country_code: string, organization_name: string, billing_timezone: string, verified_at: string}  $data
     * @return array{organization_id: int, user_id: int, handoff_token: string, handoff_expires_at: CarbonImmutable}
     *
     * @throws EmailTakenException
     * @throws \App\Exceptions\MarketUnavailableException
     */
    public function provision(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                // 1. Idempotency: a retried push (e.g. after a timeout whose
                //    first attempt actually succeeded) must not create a
                //    second tenant — re-issue a fresh handoff instead.
                $existing = Organization::where('signup_reference', $data['uuid'])->first();
                if ($existing) {
                    $admin = $existing->users()
                        ->where('access_level', AccessLevel::Organization)
                        ->orderBy('id')
                        ->firstOrFail();

                    return $this->issueHandoff($existing, $admin);
                }

                // 2. Authoritative duplicate check (soft-deleted rows count —
                //    the unique index covers them too).
                $email = Str::lower($data['email']);
                if (User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    throw new EmailTakenException;
                }

                $market = $this->markets->resolve($data['country_code']);
                $settings = PlatformSetting::current();

                $organization = new Organization([
                    'name' => $data['organization_name'],
                    'status' => true,
                    'market_id' => $market->market->id,
                    'lifecycle_status' => OrganizationLifecycleStatus::Active,
                    'billing_timezone' => $data['billing_timezone'],
                ]);
                $organization->forceFill([
                    'signup_reference' => $data['uuid'],
                    'is_test_account' => false,
                ])->save();

                $user = new User;
                $user->forceFill([
                    'organization_id' => $organization->id,
                    'name' => $data['name'],
                    'email' => $email,
                    // Already a bcrypt hash — the controller validated it
                    // (including Hash::verifyConfiguration, so the hashed
                    // cast accepts it verbatim) and this service never
                    // hashes.
                    'password' => $data['password_hash'],
                    'type' => UserType::Admin,
                    'access_level' => AccessLevel::Organization,
                    'is_active' => true,
                    // The verification moment, NOT now().
                    'email_verified_at' => CarbonImmutable::parse($data['verified_at']),
                ])->save();
                // Guard named explicitly: this app's DEFAULT guard is
                // `guardian`, but console staff roles live on `web`.
                $user->assignRole(Role::findByName('Org Admin', 'web'));

                $trialing = $settings->free_trial_enabled;
                Subscription::create([
                    'organization_id' => $organization->id,
                    'plan_key' => null,
                    'billing_cycle' => null,
                    'is_trialing' => $trialing,
                    'trial_started_at' => $trialing ? now() : null,
                    'trial_ends_at' => $trialing
                        ? now($data['billing_timezone'])
                            ->addDays($settings->default_trial_length_days)
                            ->endOfDay()
                            ->utc()
                        : null,
                    'trial_plan_key' => $trialing ? $settings->trial_plan_entitlement : null,
                    'trial_days_granted' => $trialing ? $settings->default_trial_length_days : null,
                    'payment_method_readiness' => PaymentMethodReadiness::NotSetUp,
                ]);

                return $this->issueHandoff($organization, $user);
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent insert won the race — same answer as the check.
            throw new EmailTakenException;
        }
    }

    /**
     * @return array{organization_id: int, user_id: int, handoff_token: string, handoff_expires_at: CarbonImmutable}
     */
    private function issueHandoff(Organization $organization, User $user): array
    {
        $plain = bin2hex(random_bytes(32));
        $expiresAt = CarbonImmutable::now()->addMinutes(10);

        LoginHandoff::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
            'redirect_to' => '/organizations',
        ]);

        return [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'handoff_token' => $plain,
            'handoff_expires_at' => $expiresAt,
        ];
    }
}
