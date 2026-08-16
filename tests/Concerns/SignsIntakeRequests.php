<?php

namespace Tests\Concerns;

use Illuminate\Support\Str;

/**
 * Builds valid HMAC headers for the internal signup-intake endpoints —
 * MUST stay byte-identical to VerifySignupIntakeHmac's signature string
 * (METHOD \n PATH \n TIMESTAMP \n NONCE \n sha256(body)).
 */
trait SignsIntakeRequests
{
    protected string $intakeSecret = 'test-intake-secret-test-intake-secret-test-intake-secret-64char';

    protected function configureIntake(): void
    {
        config([
            'services.signup_intake.secret' => $this->intakeSecret,
            'services.signup_intake.allowed_clients' => ['signup-1'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function intakeHeaders(string $method, string $path, string $body = '', array $overrides = []): array
    {
        $timestamp = $overrides['timestamp'] ?? (string) now()->timestamp;
        $nonce = $overrides['nonce'] ?? Str::lower(bin2hex(random_bytes(16)));
        $secret = $overrides['secret'] ?? $this->intakeSecret;

        $signature = hash_hmac('sha256', implode("\n", [
            strtoupper($method),
            $path,
            $timestamp,
            $nonce,
            hash('sha256', $body),
        ]), $secret);

        return [
            'X-Binnii-Client' => $overrides['client'] ?? 'signup-1',
            'X-Binnii-Timestamp' => $timestamp,
            'X-Binnii-Nonce' => $nonce,
            'X-Binnii-Signature' => $overrides['signature'] ?? $signature,
        ];
    }
}
