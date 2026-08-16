<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

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
