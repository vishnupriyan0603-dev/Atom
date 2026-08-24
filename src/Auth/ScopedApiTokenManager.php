<?php

namespace Atom\Auth;

/**
 * Scoped API Token Manager — Phase 36
 *
 * Cryptographic HMAC-SHA256 scoped token issuance, permission bounds,
 * expiration verification, and real-time revocation blacklist.
 */
class ScopedApiTokenManager
{
    private string $signingKey;
    private array $revocationBlacklist = [];

    public function __construct(?string $signingKey = null)
    {
        $this->signingKey = $signingKey ?? 'atom_rbac_signing_secret_key_v1_secure';
    }

    /**
     * Generates a signed scoped API token.
     *
     * @param string $userId Subject user ID.
     * @param string $tenantId Tenant workspace ID.
     * @param array $scopes List of granted scopes (e.g. ['repo:read', 'swarm:dispatch']).
     * @param int $ttlSeconds Time to live in seconds.
     * @return array Token string and metadata.
     */
    public function generateToken(string $userId, string $tenantId, array $scopes, int $ttlSeconds = 3600): array
    {
        $tokenId = 'tok_' . bin2hex(random_bytes(8));
        $now = time();
        $payload = [
            'jti'       => $tokenId,
            'sub'       => $userId,
            'tenant_id' => $tenantId,
            'scopes'    => array_values($scopes),
            'iat'       => $now,
            'exp'       => $now + $ttlSeconds,
        ];

        $payloadB64 = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $payloadB64, $this->signingKey);
        $tokenString = "atm_{$payloadB64}.{$signature}";

        return [
            'token_id'     => $tokenId,
            'token_string' => $tokenString,
            'expires_at'   => $now + $ttlSeconds,
            'scopes'       => $scopes,
        ];
    }

    /**
     * Validates a scoped API token.
     *
     * @param string $tokenString Raw token string.
     * @param string $requiredScope Optional scope required.
     * @return array Validation result and payload.
     */
    public function validateToken(string $tokenString, string $requiredScope = ''): array
    {
        if (!str_starts_with($tokenString, 'atm_') || !str_contains($tokenString, '.')) {
            return ['valid' => false, 'error' => 'Malformed token format'];
        }

        $raw = substr($tokenString, 4);
        [$payloadB64, $signature] = explode('.', $raw, 2);

        $expectedSig = hash_hmac('sha256', $payloadB64, $this->signingKey);
        if (!hash_equals($expectedSig, $signature)) {
            return ['valid' => false, 'error' => 'Invalid token cryptographic signature'];
        }

        $payload = json_decode(base64_decode($payloadB64), true);
        if (!is_array($payload)) {
            return ['valid' => false, 'error' => 'Corrupted token payload'];
        }

        // Check revocation
        $jti = $payload['jti'] ?? '';
        if (isset($this->revocationBlacklist[$jti])) {
            return ['valid' => false, 'error' => 'Token has been revoked'];
        }

        // Check expiration
        if (time() >= ($payload['exp'] ?? 0)) {
            return ['valid' => false, 'error' => 'Token has expired'];
        }

        // Check required scope
        if (!empty($requiredScope)) {
            $scopes = $payload['scopes'] ?? [];
            if (!in_array('*', $scopes, true) && !in_array($requiredScope, $scopes, true)) {
                return ['valid' => false, 'error' => "Token missing required scope '{$requiredScope}'"];
            }
        }

        return [
            'valid'   => true,
            'payload' => $payload,
        ];
    }

    /**
     * Revokes a token by ID.
     */
    public function revokeToken(string $tokenId): bool
    {
        $this->revocationBlacklist[$tokenId] = time();
        return true;
    }
}
