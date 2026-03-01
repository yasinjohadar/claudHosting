<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SignatureVerifier
{
    protected string $appSecret;

    public function __construct(string $appSecret)
    {
        $this->appSecret = $appSecret;
    }

    /**
     * Verify Meta webhook signature
     */
    public function verify(string $signature, string $payload): bool
    {
        if (empty($signature)) {
            Log::channel('whatsapp')->warning('Missing X-Hub-Signature-256 header');
            return false;
        }

        // Extract signature value (format: "sha256=...")
        if (!Str::startsWith($signature, 'sha256=')) {
            Log::channel('whatsapp')->warning('Invalid signature format', ['signature' => substr($signature, 0, 20) . '...']);
            return false;
        }

        $signatureHash = Str::after($signature, 'sha256=');

        // Calculate expected signature
        $expectedHash = hash_hmac('sha256', $payload, $this->appSecret);

        // Compare hashes using hash_equals to prevent timing attacks
        $isValid = hash_equals($expectedHash, $signatureHash);

        if (!$isValid) {
            Log::channel('whatsapp')->warning('Invalid webhook signature');
        }

        return $isValid;
    }

    /**
     * Verify signature from request
     */
    public static function verifyFromRequest(string $signature, string $payload, ?string $appSecret = null, bool $strictMode = true): bool
    {
        if (empty($appSecret)) {
            Log::channel('whatsapp')->warning('App secret not configured');
            return !$strictMode; // If strict mode is disabled and no secret, allow
        }

        $verifier = new self($appSecret);
        return $verifier->verify($signature, $payload);
    }
}




