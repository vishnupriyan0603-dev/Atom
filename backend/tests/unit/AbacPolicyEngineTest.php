<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Auth\AbacPolicyEngine;
use Atom\Auth\AbacPolicyStore;
use Atom\Security\SecretRedactor;

/**
 * Phase 48 — AbacPolicyEngine unit tests (6 tests).
 */
class AbacPolicyEngineTest extends TestCase
{
    private AbacPolicyEngine $engine;
    private array $policies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AbacPolicyEngine('DenyOverrides', new SecretRedactor());
        $store = new AbacPolicyStore();
        $this->policies = $store->listPolicies();
    }

    public function testPermitValidTopSecretVaultAccess(): void
    {
        $request = [
            'subject' => ['role' => 'admin', 'clearance_level' => 4, 'mfa_verified' => true],
            'resource' => ['type' => 'vault_secret'],
            'action' => 'read',
            'environment' => ['ip_address' => '10.2.3.4'],
        ];

        $result = $this->engine->evaluate($request, $this->policies);

        $this->assertSame('PERMIT', $result['decision']);
        $this->assertSame('POLICY_TOPSECRET_VAULT', $result['matched_policy']);
    }

    public function testDenyVaultAccessWithoutMfa(): void
    {
        $request = [
            'subject' => ['role' => 'admin', 'clearance_level' => 4, 'mfa_verified' => false],
            'resource' => ['type' => 'vault_secret'],
            'action' => 'read',
            'environment' => ['ip_address' => '10.2.3.4'],
        ];

        $result = $this->engine->evaluate($request, $this->policies);

        $this->assertSame('DENY', $result['decision']);
    }

    public function testDenyVaultAccessFromExternalIp(): void
    {
        $request = [
            'subject' => ['role' => 'admin', 'clearance_level' => 4, 'mfa_verified' => true],
            'resource' => ['type' => 'vault_secret'],
            'action' => 'read',
            'environment' => ['ip_address' => '203.0.113.10'], // External IP
        ];

        $result = $this->engine->evaluate($request, $this->policies);

        $this->assertSame('DENY', $result['decision']);
    }

    public function testPermitProdDeploymentWithHighTrustDevice(): void
    {
        $request = [
            'subject' => ['role' => 'security_officer'],
            'resource' => ['type' => 'deployment_pipeline'],
            'action' => 'deploy',
            'environment' => ['device_trust_score' => 90],
        ];

        $result = $this->engine->evaluate($request, $this->policies);

        $this->assertSame('PERMIT', $result['decision']);
        $this->assertSame('POLICY_PROD_DEPLOYMENT', $result['matched_policy']);
    }

    public function testDenyProdDeploymentWithLowTrustDevice(): void
    {
        $request = [
            'subject' => ['role' => 'lead_architect'],
            'resource' => ['type' => 'deployment_pipeline'],
            'action' => 'deploy',
            'environment' => ['device_trust_score' => 60], // Below 80
        ];

        $result = $this->engine->evaluate($request, $this->policies);

        $this->assertSame('DENY', $result['decision']);
    }

    public function testDefaultZeroTrustDenyForUnknownResource(): void
    {
        $request = [
            'subject' => ['role' => 'anonymous'],
            'resource' => ['type' => 'classified_unknown_system'],
            'action' => 'delete',
        ];

        $result = $this->engine->evaluate($request, $this->policies);

        $this->assertSame('DENY', $result['decision']);
        $this->assertNull($result['matched_policy']);
    }
}
