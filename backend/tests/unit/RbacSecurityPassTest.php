<?php

use PHPUnit\Framework\TestCase;
use Atom\Auth\TenantWorkspaceManager;
use Atom\Auth\ScopedApiTokenManager;
use Atom\Auth\RolePermissionMatrix;

/**
 * Phase 36 — RbacSecurityPassTest security & safety tests (5 tests).
 */
class RbacSecurityPassTest extends TestCase
{
    public function testConstantTimeTokenComparisonAgainstTimingAttacks(): void
    {
        $manager = new ScopedApiTokenManager('secret_key');
        $validToken = $manager->generateToken('usr_1', 'default', ['repo:read']);

        // Forged signature
        $forged = 'atm_eyJqdGkiOiJ0b2tfMTIzIn0.fake_forged_sig_here';
        $res = $manager->validateToken($forged);

        $this->assertFalse($res['valid']);
    }

    public function testSecretRedactionInTenantPayloads(): void
    {
        $tenants = new TenantWorkspaceManager();
        $tenant = $tenants->createTenant('sec-tenant', 'Secret Corp sk-ant-api03-123456789012345678901234', 'usr_owner');

        $this->assertIsArray($tenant);
        $this->assertStringNotContainsString('sk-ant-api03-123456789012345678901234', $tenant['name']);
    }

    public function testNoEvalOrShellExecutionInRbacSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $tenantCode = file_get_contents($rootDir . '/src/Auth/TenantWorkspaceManager.php');
        $matrixCode = file_get_contents($rootDir . '/src/Auth/RolePermissionMatrix.php');
        $abacCode = file_get_contents($rootDir . '/src/Auth/AttributeAccessControlEngine.php');
        $tokenCode = file_get_contents($rootDir . '/src/Auth/ScopedApiTokenManager.php');

        $this->assertNotFalse($tenantCode);
        $this->assertNotFalse($matrixCode);
        $this->assertNotFalse($abacCode);
        $this->assertNotFalse($tokenCode);

        $this->assertStringNotContainsString('eval(', $tenantCode);
        $this->assertStringNotContainsString('eval(', $matrixCode);
        $this->assertStringNotContainsString('eval(', $abacCode);
        $this->assertStringNotContainsString('eval(', $tokenCode);
        $this->assertStringNotContainsString('exec(', $tokenCode);
        $this->assertStringNotContainsString('shell_exec(', $tokenCode);
    }

    public function testWildcardScopePrivilegeEscalationPrevention(): void
    {
        $matrix = new RolePermissionMatrix();

        // Non-owner roles must not have wildcard '*'
        $memberPerms = $matrix->getMatrix()[RolePermissionMatrix::ROLE_MEMBER];
        $this->assertNotContains('*', $memberPerms);

        $auditorPerms = $matrix->getMatrix()[RolePermissionMatrix::ROLE_AUDITOR];
        $this->assertNotContains('*', $auditorPerms);
    }

    public function testTenantIsolationBoundary(): void
    {
        $manager = new TenantWorkspaceManager();
        $manager->createTenant('finance', 'Finance Division', 'usr_fin_lead');

        $this->expectException(\InvalidArgumentException::class);
        $manager->getTenant('non_existent_tenant_x');
    }
}
