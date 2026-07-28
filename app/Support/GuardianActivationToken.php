<?php

namespace App\Support;

use App\Models\Guardian;
use Illuminate\Support\Carbon;

/**
 * The token in the welcome email's activation link.
 *
 * Signed, not encrypted, and carrying only an id + expiry, so the admin
 * console can mint the same token from the shared
 * GUARDIAN_ACTIVATION_SECRET (see config/parent.php).
 *
 * Single use is enforced by state rather than storage: `complete` is the
 * only consumer, and it leaves the guardian `registered`, which `resolve()`
 * then refuses. Re-inviting a guardian sets them back to `invited` and their
 * outstanding links start working again — which is what re-inviting means.
 */
final class GuardianActivationToken
{
    public static function for(Guardian $guardian): string
    {
        return self::sign([
            'id' => $guardian->getKey(),
            'exp' => now()->addDays(config('parent.activation_ttl_days'))->getTimestamp(),
        ]);
    }

    /** The guardian this token activates, or null if it is invalid, expired or spent. */
    public static function resolve(?string $token): ?Guardian
    {
        $payload = self::verify($token);

        if ($payload === null || Carbon::createFromTimestamp($payload['exp'])->isPast()) {
            return null;
        }

        $guardian = Guardian::find($payload['id']);

        if ($guardian === null || $guardian->canLogIn()) {
            return null;
        }

        return $guardian;
    }

    public static function link(Guardian $guardian): string
    {
        return config('parent.app_url').'/activate?token='.urlencode(self::for($guardian));
    }

    /** @param  array{id: int, exp: int}  $payload */
    private static function sign(array $payload): string
    {
        $body = self::encode(json_encode($payload, JSON_THROW_ON_ERROR));

        return $body.'.'.self::encode(hash_hmac('sha256', $body, self::secret(), binary: true));
    }

    /** @return array{id: int, exp: int}|null */
    private static function verify(?string $token): ?array
    {
        if ($token === null || substr_count($token, '.') !== 1) {
            return null;
        }

        [$body, $signature] = explode('.', $token);
        $expected = self::encode(hash_hmac('sha256', $body, self::secret(), binary: true));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode(self::decode($body), true);

        return is_array($payload) && isset($payload['id'], $payload['exp']) ? $payload : null;
    }

    private static function secret(): string
    {
        return (string) config('parent.activation_secret');
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function decode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), strict: false);
    }
}
