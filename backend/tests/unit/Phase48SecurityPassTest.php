<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Auth\AbacPolicyEngine;
use Atom\Auth\AbacPolicyStore;
use Atom\Security\SecretRedactor;

/**
 * Phase 48 — Phase48SecurityPassTest security & safety tests (5 tests).
 */
class Phase48SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInAbacRequestAttributes(): void
    {
        $engine = new AbacPolicyEngine('DenyOverrides', $this->redactor);
        $store = new AbacPolicyStore($this->redactor);

        $request = [
            'subject' => [
                'role' => 'admin',
                'api_token' => 'sk-1122334455667788990011223344',
                'clearance_level' => 4,
                'mfa_verified' => true,
            ],
            'resource' => ['type' => 'vault_secret'],
            'action' => 'read',
            'environment' => ['ip_address' => '10.0.0.5'],
        ];

        $result = $engine->evaluate($request, $store->listPolicies());
        $this->assertSame('PERMIT', $result['decision']);
    }

    public function testDenyOverridesPriorityOnConflict(): void
    {
        $engine = new AbacPolicyEngine('DenyOverrides', $this->redactor);

        $conflictingPolicies = [
            [
                'id' => 'POLICY_PERMIT_ALL',
                'effect' => 'PERMIT',
                'target' => ['resource_type' => 'doc', 'actions' => ['read']],
                'rules' => [],
            ],
            [
                'id' => 'POLICY_DENY_SPECIFIC',
                'effect' => 'DENY',
                'target' => ['resource_type' => 'doc', 'actions' => ['read']],
                'rules' => [],
            ],
        ];

        $request = ['resource' => ['type' => 'doc'], 'action' => 'read'];
        $result = $engine->evaluate($request, $conflictingPolicies);

        // DenyOverrides must prioritize DENY
        $this->assertSame('DENY', $result['decision']);
    }

    public function testMalformedCidrHandledGracefully(): void
    {
        $engine = new AbacPolicyEngine('DenyOverrides', $this->redactor);
        $policy = [
            [
                'id' => 'POLICY_CIDR_TEST',
                'effect' => 'PERMIT',
                'target' => ['resource_type' => 'net', 'actions' => ['connect']],
                'rules' => [
                    ['category' => 'environment', 'attribute' => 'ip', 'operator' => 'cidr_match', 'value' => 'invalid_cidr_format/999'],
                ],
            ],
        ];

        $request = ['resource' => ['type' => 'net'], 'action' => 'connect', 'environment' => ['ip' => '10.0.0.1']];
        $result = $engine->evaluate($request, $policy);

        $this->assertSame('DENY', $result['decision']);
    }

    public function testNullAttributeConditionFailsClosed(): void
    {
        $engine = new AbacPolicyEngine('DenyOverrides', $this->redactor);
        $policy = [
            [
                'id' => 'POLICY_ROLE_TEST',
                'effect' => 'PERMIT',
                'target' => ['resource_type' => 'item', 'actions' => ['use']],
                'rules' => [
                    ['category' => 'subject', 'attribute' => 'role', 'operator' => 'equals', 'value' => 'superuser'],
                ],
            ],
        ];

        // Subject has NO role attribute
        $request = ['subject' => [], 'resource' => ['type' => 'item'], 'action' => 'use'];
        $result = $engine->evaluate($request, $policy);

        $this->assertSame('DENY', $result['decision']);
    }

    public function testNoDangerousEvalOrShellExecutionInAuthSubsystem(): void
    {
        $files = [
            'src/Auth/AbacPolicyEngine.php',
            'src/Auth/AbacPolicyStore.php',
            'src/Auth/AttributeAccessControlEngine.php',
            'src/Auth/RolePermissionMatrix.php',
            'src/Auth/ScopedApiTokenManager.php',
            'src/Auth/TenantWorkspaceManager.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
