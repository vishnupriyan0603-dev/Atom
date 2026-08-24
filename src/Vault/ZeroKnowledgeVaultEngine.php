<?php

namespace Atom\Vault;

use Atom\Security\SecretRedactor;

/**
 * Zero-Knowledge Vault Engine — Phase 33
 *
 * Client-side AES-256-GCM authenticated encryption and decryption,
 * cryptographic PBKDF2 key derivation, per-payload salts, and tamper detection.
 */
class ZeroKnowledgeVaultEngine
{
    public const CIPHER_ALGO = 'aes-256-gcm';
    public const PBKDF2_HASH = 'sha256';
    public const PBKDF2_ITERATIONS = 10000;
    public const KEY_LENGTH = 32; // 256 bits
    public const IV_LENGTH = 12;  // 96 bits for GCM
    public const TAG_LENGTH = 16; // 128 bits for GCM

    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Derives a 256-bit symmetric encryption key from a master passphrase and cryptographic salt.
     */
    public function deriveKey(string $passphrase, string $salt): string
    {
        if (strlen($passphrase) < 8) {
            throw new \InvalidArgumentException('Passphrase must be at least 8 characters long');
        }
        return hash_pbkdf2(self::PBKDF2_HASH, $passphrase, $salt, self::PBKDF2_ITERATIONS, self::KEY_LENGTH, true);
    }

    /**
     * Encrypts plaintext using AES-256-GCM with a fresh random IV and salt.
     *
     * @param string $plaintext Data to encrypt.
     * @param string $passphrase Master secret passphrase.
     * @param string|null $salt Optional custom salt (hex string).
     * @return array Encrypted payload packet.
     */
    public function encrypt(string $plaintext, string $passphrase, ?string $salt = null): array
    {
        if ($plaintext === '') {
            throw new \InvalidArgumentException('Plaintext cannot be empty');
        }

        $rawSalt = $salt !== null ? hex2bin($salt) : random_bytes(16);
        $key = $this->deriveKey($passphrase, $rawSalt);
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER_ALGO,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return [
            'algorithm'  => self::CIPHER_ALGO,
            'ciphertext' => base64_encode($ciphertext),
            'iv'         => base64_encode($iv),
            'tag'        => base64_encode($tag),
            'salt'       => bin2hex($rawSalt),
            'created_at' => date('c'),
        ];
    }

    /**
     * Decrypts an AES-256-GCM encrypted payload packet.
     *
     * @param array $payload Encrypted packet containing ciphertext, iv, tag, and salt.
     * @param string $passphrase Master secret passphrase.
     * @return string Decrypted plaintext.
     */
    public function decrypt(array $payload, string $passphrase): string
    {
        $requiredKeys = ['ciphertext', 'iv', 'tag', 'salt'];
        foreach ($requiredKeys as $k) {
            if (!isset($payload[$k])) {
                throw new \InvalidArgumentException("Missing required payload field: '{$k}'");
            }
        }

        $rawSalt = hex2bin($payload['salt']);
        if ($rawSalt === false) {
            throw new \InvalidArgumentException('Invalid salt format');
        }

        $key = $this->deriveKey($passphrase, $rawSalt);
        $ciphertext = base64_decode($payload['ciphertext']);
        $iv = base64_decode($payload['iv']);
        $tag = base64_decode($payload['tag']);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER_ALGO,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed: Authentication tag mismatch or invalid passphrase');
        }

        return $plaintext;
    }
}
