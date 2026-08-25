<?php

namespace Atom\Auth;

use Atom\Security\SecretRedactor;

/**
 * AbacPolicyStore — Phase 48
 * In-memory & persisted repository for dynamic ABAC security policies.
 */
class AbacPolicyStore
{
    private SecretRedactor $redactor;
    private array $policies = [];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->loadDefaultPolicies();
    }

    public function addPolicy(array $policy): void
    {
        $id = $policy['id'] ?? ('policy_' . uniqid());
        $policy['id'] = $id;
        $this->policies[$id] = $policy;
    }

    public function getPolicy(string $id): ?array
    {
        return $this->policies[$id] ?? null;
    }

    public function listPolicies(): array
    {
        return array_values($this->policies);
    }

    public function removePolicy(string $id): bool
    {
        if (isset($this->policies[$id])) {
            unset($this->policies[$id]);
            return true;
        }
        return false;
    }

    public function count(): int
    {
        return count($this->policies);
    }

    private function loadDefaultPolicies(): void
    {
        $this->addPolicy([
            'id' => 'POLICY_TOPSECRET_VAULT',
            'title' => 'Top-Secret Vault Access Controls',
            'effect' => 'PERMIT',
            'target' => [
                'resource_type' => ['vault_secret', 'quantum_key'],
                'actions' => ['read', 'write', 'decrypt'],
            ],
            'rules' => [
                ['category' => 'subject', 'attribute' => 'clearance_level', 'operator' => 'greater_equals', 'value' => 3],
                ['category' => 'subject', 'attribute' => 'mfa_verified', 'operator' => 'is_true', 'value' => true],
                ['category' => 'environment', 'attribute' => 'ip_address', 'operator' => 'cidr_match', 'value' => '10.0.0.0/8'],
            ],
        ]);

        $this->addPolicy([
            'id' => 'POLICY_PROD_DEPLOYMENT',
            'title' => 'Production Deployment Authorization Gate',
            'effect' => 'PERMIT',
            'target' => [
                'resource_type' => ['deployment_pipeline', 'cluster_infrastructure'],
                'actions' => ['deploy', 'execute', 'terminate'],
            ],
            'rules' => [
                ['category' => 'subject', 'attribute' => 'role', 'operator' => 'in', 'value' => ['admin', 'security_officer', 'lead_architect']],
                ['category' => 'environment', 'attribute' => 'device_trust_score', 'operator' => 'greater_equals', 'value' => 80],
            ],
        ]);

        $this->addPolicy([
            'id' => 'POLICY_PUBLIC_RESOURCE_READ',
            'title' => 'Public Knowledge Read Access',
            'effect' => 'PERMIT',
            'target' => [
                'resource_type' => ['public_document', 'voice_preset', 'api_docs'],
                'actions' => ['read', 'view'],
            ],
            'rules' => [
                ['category' => 'subject', 'attribute' => 'is_authenticated', 'operator' => 'is_true', 'value' => true],
            ],
        ]);
    }
}
