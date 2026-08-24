<?php

namespace Atom\Vault;

/**
 * Passphrase Authentication Gate — Phase 33
 *
 * Constant-time master passphrase verification, brute-force lockout,
 * and timed vault session token management.
 */
class PassphraseAuthGate
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_SECONDS = 60;
    public const TOKEN_TTL_SECONDS = 3600;

    private static array $failedAttempts = [];
    private static array $activeSessions = [];
    private string $masterHash;

    public function __construct(?string $masterPassphrase = null)
    {
        $pass = $masterPassphrase ?? 'atom_master_vault_pass_2026';
        $this->masterHash = hash('sha256', $pass);
    }

    /**
     * Attempts to unlock the vault and obtain a session token.
     *
     * @param string $passphrase Attempted passphrase.
     * @param string $clientId Identifier for rate limiting (e.g. IP or device ID).
     * @return array Authentication outcome and session token if successful.
     */
    public function unlock(string $passphrase, string $clientId = 'default_client'): array
    {
        $now = time();

        // Check brute force lockout
        if (isset(self::$failedAttempts[$clientId])) {
            $record = self::$failedAttempts[$clientId];
            if ($record['count'] >= self::MAX_ATTEMPTS && ($now - $record['last_attempt']) < self::LOCKOUT_SECONDS) {
                $remaining = self::LOCKOUT_SECONDS - ($now - $record['last_attempt']);
                throw new \RuntimeException("Vault locked due to repeated failed attempts. Retry in {$remaining} seconds.");
            }
        }

        $inputHash = hash('sha256', $passphrase);
        if (!hash_equals($this->masterHash, $inputHash)) {
            // Record failure
            $count = (self::$failedAttempts[$clientId]['count'] ?? 0) + 1;
            self::$failedAttempts[$clientId] = [
                'count'        => $count,
                'last_attempt' => $now,
            ];
            throw new \RuntimeException('Invalid vault passphrase');
        }

        // Reset failed attempts on success
        unset(self::$failedAttempts[$clientId]);

        // Issue session token
        $token = bin2hex(random_bytes(24));
        self::$activeSessions[$token] = [
            'client_id'  => $clientId,
            'expires_at' => $now + self::TOKEN_TTL_SECONDS,
        ];

        return [
            'unlocked'   => true,
            'token'      => $token,
            'expires_in' => self::TOKEN_TTL_SECONDS,
            'message'    => 'Vault successfully unlocked',
        ];
    }

    /**
     * Validates if a vault session token is active and unexpired.
     */
    public function validateToken(string $token): bool
    {
        if (empty($token) || !isset(self::$activeSessions[$token])) {
            return false;
        }

        $session = self::$activeSessions[$token];
        if (time() > $session['expires_at']) {
            unset(self::$activeSessions[$token]);
            return false;
        }

        return true;
    }

    /**
     * Invalidates a session token (locks the vault).
     */
    public function lock(string $token): bool
    {
        if (isset(self::$activeSessions[$token])) {
            unset(self::$activeSessions[$token]);
            return true;
        }
        return false;
    }

    /**
     * Clears all session states (for testing).
     */
    public static function reset(): void
    {
        self::$failedAttempts = [];
        self::$activeSessions = [];
    }
}
