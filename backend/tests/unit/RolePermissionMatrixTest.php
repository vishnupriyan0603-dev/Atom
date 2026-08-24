<?php

use PHPUnit\Framework\TestCase;
use Atom\Auth\RolePermissionMatrix;

/**
 * Phase 36 — RolePermissionMatrix unit tests (5 tests).
 */
class RolePermissionMatrixTest extends TestCase
{
    private RolePermissionMatrix $matrix;

    protected function setUp(): void
    {
        $this->matrix = new RolePermissionMatrix();
    }

    public function testOwnerRoleHasUniversalWildcard(): void
    {
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_OWNER, 'repo:read'));
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_OWNER, 'vault:decrypt'));
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_OWNER, 'custom:wildcard_action'));
    }

    public function testAdminRoleHasVaultAndPluginPermissions(): void
    {
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_ADMIN, 'vault:decrypt'));
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_ADMIN, 'plugin:install'));
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_ADMIN, 'admin:manage_users'));
    }

    public function testMemberRoleLacksVaultAndAdminPermissions(): void
    {
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_MEMBER, 'repo:read'));
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_MEMBER, 'repo:write'));
        $this->assertFalse($this->matrix->hasPermission(RolePermissionMatrix::ROLE_MEMBER, 'vault:decrypt'));
        $this->assertFalse($this->matrix->hasPermission(RolePermissionMatrix::ROLE_MEMBER, 'admin:manage_users'));
    }

    public function testAuditorRoleHasReadOnlyPermissions(): void
    {
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_AUDITOR, 'audit:view'));
        $this->assertTrue($this->matrix->hasPermission(RolePermissionMatrix::ROLE_AUDITOR, 'vault:merkle_inspect'));
        $this->assertFalse($this->matrix->hasPermission(RolePermissionMatrix::ROLE_AUDITOR, 'repo:write'));
        $this->assertFalse($this->matrix->hasPermission(RolePermissionMatrix::ROLE_AUDITOR, 'swarm:dispatch'));
    }

    public function testUnknownRoleDeniesPermission(): void
    {
        $this->assertFalse($this->matrix->hasPermission('UNKNOWN_GUEST_ROLE', 'repo:read'));
    }
}
