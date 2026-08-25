<?php

namespace Atom\Config;

use Atom\Security\SecretRedactor;

/**
 * FeatureFlagRolloutEngine — Phase 77
 * Dynamic multi-tenant feature flag matrix, percentage-based gradual rollout, and user/tenant affinity engine.
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
     * Define or update a feature flag.
     */
    public function setFlag(string $flagKey, bool $enabled = true, int $rolloutPct = 100, array $whitelistTenants = [], array $whitelistUsers = []): bool
    {
        $cleanKey = trim(strtolower($this->redactor->redact($flagKey)));

        $this->flags[$cleanKey] = [
            'key' => $cleanKey,
            'enabled' => $enabled,
            'rollout_pct' => max(0, min(100, $rolloutPct)),
            'whitelist_tenants' => array_map('strtolower', $whitelistTenants),
            'whitelist_users' => array_map('strtolower', $whitelistUsers),
            'updated_at' => microtime(true),
        ];

        return true;
    }

    /**
     * Evaluate if a feature flag is active for a given tenant or user context.
     *
     * @param string $flagKey
     * @param string $userId
     * @param string $tenantId
     * @return array [ 'is_active' => bool, 'reason' => string, 'flag' => string ]
     */
    public function evaluate(string $flagKey, string $userId = 'guest', string $tenantId = 'default'): array
    {
        $cleanKey = trim(strtolower($this->redactor->redact($flagKey)));
        $cleanUser = trim(strtolower($this->redactor->redact($userId)));
        $cleanTenant = trim(strtolower($this->redactor->redact($tenantId)));

        if (!isset($this->flags[$cleanKey])) {
            return [
                'flag' => $cleanKey,
                'is_active' => false,
                'reason' => 'FLAG_NOT_FOUND_DEFAULT_DISABLED',
            ];
        }

        $flag = $this->flags[$cleanKey];

        // 1. Master enable toggle
        if (!$flag['enabled'] || $flag['rollout_pct'] === 0) {
            return [
                'flag' => $cleanKey,
                'is_active' => false,
                'reason' => 'MASTER_SWITCH_DISABLED',
            ];
        }

        // 2. User Whitelist
        if (in_array($cleanUser, $flag['whitelist_users'], true)) {
            return [
                'flag' => $cleanKey,
                'is_active' => true,
                'reason' => 'USER_WHITELIST_MATCH',
            ];
        }

        // 3. Tenant Whitelist
        if (in_array($cleanTenant, $flag['whitelist_tenants'], true)) {
            return [
                'flag' => $cleanKey,
                'is_active' => true,
                'reason' => 'TENANT_WHITELIST_MATCH',
            ];
        }

        // 4. Percentage-Based Rollout
        if ($flag['rollout_pct'] >= 100) {
            return [
                'flag' => $cleanKey,
                'is_active' => true,
                'reason' => 'FULL_ROLLOUT_100_PCT',
            ];
        }

        $hash = abs(crc32($cleanKey . ':' . $cleanUser . ':' . $cleanTenant)) % 100;
        $isActive = $hash < $flag['rollout_pct'];

        return [
            'flag' => $cleanKey,
            'is_active' => $isActive,
            'reason' => $isActive ? "PERCENTAGE_ROLLOUT_ENABLED ({$flag['rollout_pct']}%)" : "PERCENTAGE_ROLLOUT_EXCLUDED ({$flag['rollout_pct']}%)",
        ];
    }

    public function getAllFlags(): array
    {
        return array_values($this->flags);
    }

    private function seedSampleFlags(): void
    {
        $this->setFlag('beta_voice_cloning', true, 25, ['tenant_vip'], ['user_alex']);
        $this->setFlag('post_quantum_v2', true, 100, [], []);
        $this->setFlag('legacy_xml_export', false, 0, [], []);
        $this->setFlag('iot_telemetry_mesh', true, 50, ['tenant_edge'], []);
    }
}
