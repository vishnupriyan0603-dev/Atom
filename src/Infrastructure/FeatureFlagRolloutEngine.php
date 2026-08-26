<?php

namespace Atom\Infrastructure;

use Atom\Security\SecretRedactor;

/**
 * FeatureFlagRolloutEngine — Phase 95
 * Dynamic percentage rollout engine, deterministic user partition hashing (CRC32), multi-variant A/B/n testing, and kill-switch governor.
 */
class FeatureFlagRolloutEngine
{
    private SecretRedactor $redactor;
    private array $flags = [];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleFlags();
    }

    /**
     * Register or update a feature flag.
     */
    public function registerFlag(
        string $flagKey,
        bool $enabled = true,
        int $rolloutPct = 100,
        array $allowedRoles = [],
        array $variants = []
    ): bool {
        $cleanKey = trim(strtolower($this->redactor->redact($flagKey)));

        $this->flags[$cleanKey] = [
            'flag_key' => $cleanKey,
            'enabled' => $enabled,
            'rollout_pct' => max(0, min(100, $rolloutPct)),
            'allowed_roles' => array_map('strtolower', $allowedRoles),
            'variants' => $variants ?: ['control' => 50, 'treatment' => 50],
            'evaluations_count' => 0,
            'enabled_count' => 0,
        ];

        return true;
    }

    /**
     * Evaluate a feature flag for a given user context.
     *
     * @param string $flagKey Target flag key
     * @param string $userId Unique user identifier for deterministic bucketing
     * @param array $userAttributes Contextual attributes (e.g. ['role' => 'admin', 'tenant' => 'acme'])
     * @return array Evaluation result envelope [enabled, variant, reason]
     */
    public function evaluate(string $flagKey, string $userId = 'user_guest', array $userAttributes = []): array
    {
        $cleanKey = trim(strtolower($this->redactor->redact($flagKey)));
        $cleanUser = trim($this->redactor->redact($userId));

        if (!isset($this->flags[$cleanKey])) {
            return [
                'enabled' => false,
                'variant' => 'control',
                'reason' => 'FLAG_NOT_FOUND',
                'flag_key' => $cleanKey,
                'user_id' => $cleanUser,
            ];
        }

        $flag = &$this->flags[$cleanKey];
        $flag['evaluations_count']++;

        // 1. Check Global Kill-Switch
        if (!$flag['enabled']) {
            return [
                'enabled' => false,
                'variant' => 'control',
                'reason' => 'FLAG_GLOBALLY_DISABLED',
                'flag_key' => $cleanKey,
                'user_id' => $cleanUser,
            ];
        }

        // 2. Check Role Targeting Overrides
        if (!empty($flag['allowed_roles'])) {
            $userRole = strtolower(trim($userAttributes['role'] ?? 'guest'));
            if (in_array($userRole, $flag['allowed_roles'], true)) {
                $flag['enabled_count']++;
                return [
                    'enabled' => true,
                    'variant' => 'treatment',
                    'reason' => 'ROLE_TARGETING_MATCH',
                    'flag_key' => $cleanKey,
                    'user_id' => $cleanUser,
                ];
            }
        }

        // 3. Deterministic Percentage Rollout Partition (0-99)
        $hashInput = "{$cleanKey}:{$cleanUser}";
        $bucket = (int) (sprintf('%u', crc32($hashInput)) % 100);

        if ($bucket >= $flag['rollout_pct']) {
            return [
                'enabled' => false,
                'variant' => 'control',
                'reason' => 'OUTSIDE_ROLLOUT_PERCENTAGE',
                'bucket' => $bucket,
                'flag_key' => $cleanKey,
                'user_id' => $cleanUser,
            ];
        }

        // 4. Multi-Variant A/B/n Partitioning
        $variantSelected = 'control';
        $variantBucket = $bucket % array_sum($flag['variants']);
        $accum = 0;
        foreach ($flag['variants'] as $varName => $weight) {
            $accum += $weight;
            if ($variantBucket < $accum) {
                $variantSelected = $varName;
                break;
            }
        }

        $flag['enabled_count']++;

        return [
            'enabled' => true,
            'variant' => $variantSelected,
            'reason' => 'ROLLOUT_PERCENTAGE_MATCH',
            'bucket' => $bucket,
            'flag_key' => $cleanKey,
            'user_id' => $cleanUser,
        ];
    }

    public function setFlagRollout(string $flagKey, int $rolloutPct): bool
    {
        $cleanKey = trim(strtolower($flagKey));
        if (isset($this->flags[$cleanKey])) {
            $this->flags[$cleanKey]['rollout_pct'] = max(0, min(100, $rolloutPct));
            return true;
        }
        return false;
    }

    public function toggleFlag(string $flagKey, bool $enabled): bool
    {
        $cleanKey = trim(strtolower($flagKey));
        if (isset($this->flags[$cleanKey])) {
            $this->flags[$cleanKey]['enabled'] = $enabled;
            return true;
        }
        return false;
    }

    public function getAllFlags(): array
    {
        return array_values($this->flags);
    }

    private function seedSampleFlags(): void
    {
        $this->registerFlag('quantum_encryption_handshake', true, 50, ['admin', 'beta_tester']);
        $this->registerFlag('ai_streaming_fast_mode', true, 100, [], ['control' => 50, 'fast_v2' => 50]);
        $this->registerFlag('legacy_soap_fallback', false, 0); // Emergency kill-switch
    }
}
