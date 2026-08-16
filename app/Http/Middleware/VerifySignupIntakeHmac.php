<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * HMAC authentication for the internal signup-intake endpoints
 * (specs/saas/build-signup-staged-registration.md §6.2).
 *
 * Signature string:
 *   METHOD + "\n" + PATH + "\n" + TIMESTAMP + "\n" + NONCE + "\n" + sha256(raw body)
 *
 * Every failure returns the SAME 401 body — no hints about which check
 * failed. Timestamp window (±60s) and a single-use nonce (cached 10 min)
 * both guard against replay; production adds mTLS at nginx on top.
 */
class VerifySignupIntakeHmac
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.signup_intake.secret');
        $client = (string) $request->header('X-Binnii-Client');
        $timestamp = (string) $request->header('X-Binnii-Timestamp');
        $nonce = (string) $request->header('X-Binnii-Nonce');
        $signature = (string) $request->header('X-Binnii-Signature');

        $valid = $secret !== ''
            && in_array($client, config('services.signup_intake.allowed_clients', []), true)
            && ctype_digit($timestamp)
            && abs(now()->timestamp - (int) $timestamp) <= 60
            && preg_match('/^[0-9a-f]{32}$/', $nonce) === 1
            && $signature !== ''
            // hash_equals, never == (timing-safe).
            && hash_equals($this->expectedSignature($request, $secret, $timestamp, $nonce), $signature)
            // Cache::add is atomic: false means the nonce was already used.
            && Cache::add('signup-intake-nonce:'.$nonce, true, 600);

        if (! $valid) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $next($request);
    }

    private function expectedSignature(Request $request, string $secret, string $timestamp, string $nonce): string
    {
        $payload = implode("\n", [
            $request->method(),
            $request->getPathInfo(),
            $timestamp,
            $nonce,
            hash('sha256', $request->getContent()),
        ]);

        return hash_hmac('sha256', $payload, $secret);
    }
}
