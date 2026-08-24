<?php

namespace Atom\Marketplace;

use Atom\Security\SecretRedactor;

/**
 * Plugin Package Signer — Phase 32
 *
 * Provides cryptographic package integrity hashing (SHA-256), HMAC manifest
 * signing, author authenticity validation, and compatibility verification.
 */
class PluginPackageSigner
{
    public const ATOM_CORE_VERSION = '1.0.0';
    public const DEFAULT_SECRET = 'atom_plugin_signer_master_key_2026';

    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Generates a cryptographic signature for a plugin manifest.
     */
    public function signManifest(array $manifest, string $secretKey = self::DEFAULT_SECRET): string
    {
        $canonical = $this->canonicalizeManifest($manifest);
        return hash_hmac('sha256', $canonical, $secretKey);
    }

    /**
     * Verifies that the manifest signature is authentic and untampered.
     */
    public function verifySignature(array $manifest, string $signature, string $secretKey = self::DEFAULT_SECRET): bool
    {
        if (empty($signature)) {
            return false;
        }

        $expected = $this->signManifest($manifest, $secretKey);
        return hash_equals($expected, $signature);
    }

    /**
     * Verifies package payload SHA-256 checksum.
     */
    public function verifyChecksum(string $payload, string $expectedChecksum): bool
    {
        if (empty($payload) || empty($expectedChecksum)) {
            return false;
        }

        $actual = hash('sha256', $payload);
        return hash_equals(strtolower($expectedChecksum), strtolower($actual));
    }

    /**
     * Validates required manifest structure, permissions schema, and version constraints.
     */
    public function validateManifestSchema(array $manifest): array
    {
        $requiredFields = ['id', 'name', 'version', 'author', 'permissions', 'capabilities'];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($manifest[$field]) || (is_string($manifest[$field]) && trim($manifest[$field]) === '')) {
                $errors[] = "Missing mandatory manifest field: '{$field}'";
            }
        }

        if (isset($manifest['id']) && !preg_match('/^[a-z0-9\-_]+$/i', $manifest['id'])) {
            $errors[] = "Plugin ID '{$manifest['id']}' contains invalid characters (alphanumeric, hyphens, underscores only)";
        }

        if (isset($manifest['permissions']) && !is_array($manifest['permissions'])) {
            $errors[] = "Manifest field 'permissions' must be an array";
        }

        if (isset($manifest['capabilities']) && !is_array($manifest['capabilities'])) {
            $errors[] = "Manifest field 'capabilities' must be an array";
        }

        // Validate version compatibility
        if (isset($manifest['min_core_version'])) {
            if (version_compare(self::ATOM_CORE_VERSION, $manifest['min_core_version'], '<')) {
                $errors[] = "Plugin requires ATOM core version >= {$manifest['min_core_version']}, current version is " . self::ATOM_CORE_VERSION;
            }
        }

        return [
            'valid'  => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Deterministic canonical serialization of manifest for signing.
     */
    private function canonicalizeManifest(array $manifest): string
    {
        // Strip non-signed volatile runtime fields if present
        $signable = $manifest;
        unset($signable['signature'], $signable['installed_at'], $signable['status']);
        ksort($signable);

        return json_encode($signable, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
