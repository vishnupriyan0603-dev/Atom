<?php

namespace Atom\Auth;

/**
 * Attribute-Based Access Control (ABAC) Engine — Phase 36
 *
 * Evaluates dynamic policies combining subject attributes, environment context,
 * and data classification levels (PUBLIC, INTERNAL, CONFIDENTIAL, RESTRICTED).
 */
class AttributeAccessControlEngine
{
    public const CLASSIFICATION_PUBLIC       = 'PUBLIC';
    public const CLASSIFICATION_INTERNAL     = 'INTERNAL';
    public const CLASSIFICATION_CONFIDENTIAL = 'CONFIDENTIAL';
    public const CLASSIFICATION_RESTRICTED   = 'RESTRICTED';

    /**
     * Evaluates access request against dynamic ABAC policies.
     *
     * @param array $subject Subject attributes (user_id, role, department, mfa_enabled)
     * @param string $action Action attempted (read, write, decrypt, execute)
     * @param array $resource Resource attributes (id, classification, owner_tenant)
     * @param array $environment Environment context (ip_address, allowed_ips, hour_of_day)
     * @return array Evaluation decision and reasons.
     */
    public function evaluate(array $subject, string $action, array $resource, array $environment = []): array
    {
        if (empty($subject)) {
            return [
                'allowed' => false,
                'reason'  => 'Deny: Anonymous subject attributes missing (Fail-Closed)',
            ];
        }

        $classification = strtoupper($resource['classification'] ?? self::CLASSIFICATION_INTERNAL);

        // 1. Public resources are accessible to any authenticated subject for read action
        if ($classification === self::CLASSIFICATION_PUBLIC && $action === 'read') {
            return [
                'allowed' => true,
                'reason'  => 'Allow: Resource classification is PUBLIC',
            ];
        }

        // 2. RESTRICTED classification requires MFA verification
        if ($classification === self::CLASSIFICATION_RESTRICTED) {
            $mfaEnabled = !empty($subject['mfa_enabled']);
            if (!$mfaEnabled) {
                return [
                    'allowed' => false,
                    'reason'  => 'Deny: Multi-Factor Authentication (MFA) required for RESTRICTED resources',
                ];
            }
        }

        // 3. Environment IP restriction check
        if (!empty($environment['allowed_ips']) && !empty($environment['ip_address'])) {
            $clientIp = $environment['ip_address'];
            $allowedList = (array)$environment['allowed_ips'];
            if (!in_array($clientIp, $allowedList, true)) {
                return [
                    'allowed' => false,
                    'reason'  => "Deny: Client IP {$clientIp} is outside permitted environment whitelist",
                ];
            }
        }

        // 4. Role eligibility for CONFIDENTIAL / RESTRICTED
        if (in_array($classification, [self::CLASSIFICATION_CONFIDENTIAL, self::CLASSIFICATION_RESTRICTED], true)) {
            $role = strtoupper($subject['role'] ?? '');
            if (!in_array($role, ['OWNER', 'ADMIN'], true)) {
                return [
                    'allowed' => false,
                    'reason'  => "Deny: Role '{$role}' lacks elevation for {$classification} assets",
                ];
            }
        }

        return [
            'allowed' => true,
            'reason'  => 'Allow: Dynamic ABAC policy constraints satisfied',
        ];
    }
}
