<?php

namespace App\Http\Controllers\Internal;

use App\Exceptions\EmailTakenException;
use App\Exceptions\MarketUnavailableException;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\SignupProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Internal tenant-intake endpoints for the signup.binnii.com push worker
 * (specs/saas/build-signup-staged-registration.md §6). Machine-to-machine
 * ONLY: HMAC (+ mTLS in production) behind an nginx source-IP allowlist —
 * never auth:guardian, never sessions/cookies, never listed in any public
 * API documentation. Responses never contain stack traces, SQL, or any
 * existing customer's data.
 */
class SignupIntakeController extends Controller
{
    /** Must match the signup form's timezone whitelist. */
    private const array TIMEZONES = [
        'America/Vancouver', 'America/Edmonton', 'America/Winnipeg',
        'America/Toronto', 'America/Halifax', 'America/St_Johns',
    ];

    public function store(Request $request, SignupProvisioner $provisioner): JsonResponse
    {
        // Re-validate EVERYTHING — the payload is not trusted (§6.4), and
        // only these keys are ever read: plan_key / lifecycle_status /
        // is_test_account / access_level / type / ids in the payload are
        // ignored outright.
        $validator = Validator::make($request->only([
            'uuid', 'name', 'email', 'password_hash', 'country_code',
            'organization_name', 'billing_timezone', 'verified_at',
        ]), [
            'uuid' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:190'],
            // Must already BE a bcrypt hash — plaintext is never accepted
            // and this endpoint never hashes. verifyConfiguration also
            // rejects absurd costs (slow-verify DoS) above our own rounds.
            'password_hash' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! is_string($value)
                    || (password_get_info($value)['algoName'] ?? null) !== 'bcrypt'
                    || ! \Illuminate\Support\Facades\Hash::verifyConfiguration($value)) {
                    $fail('not an acceptable bcrypt hash');
                }
            }],
            'country_code' => ['required', 'string', 'size:2'],
            'organization_name' => ['required', 'string', 'max:150'],
            'billing_timezone' => ['required', 'in:'.implode(',', self::TIMEZONES)],
            'verified_at' => ['required', 'date', 'before_or_equal:now'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'validation_failed',
                'fields' => collect($validator->errors()->toArray())->map(fn ($messages) => $messages[0])->all(),
            ], 422);
        }

        try {
            $result = $provisioner->provision($validator->validated());
        } catch (EmailTakenException) {
            // Never overwrite an existing account, never leak its fields.
            return response()->json(['error' => 'email_taken'], 409);
        } catch (MarketUnavailableException $e) {
            return response()->json(['error' => 'market_unavailable', 'reference' => $e->reference], 503);
        }

        return response()->json([
            'organization_id' => $result['organization_id'],
            'user_id' => $result['user_id'],
            'handoff_token' => $result['handoff_token'],
            'handoff_expires_at' => $result['handoff_expires_at']->toIso8601String(),
        ], 201);
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'provisioned_24h' => Organization::whereNotNull('signup_reference')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ]);
    }
}
