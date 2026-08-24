<?php

namespace App\Controllers\Api;

use Atom\Auth\TenantWorkspaceManager;
use Atom\Auth\RolePermissionMatrix;
use Atom\Auth\AttributeAccessControlEngine;
use Atom\Auth\ScopedApiTokenManager;

/**
 * Enterprise Multi-Tenant RBAC & ABAC API Controller — Phase 36
 *
 * Endpoints:
 * - POST /api/v1/rbac/tenant/create   — Provision new isolated workspace tenant
 * - POST /api/v1/rbac/check           — Evaluate RBAC/ABAC permission request
 * - POST /api/v1/rbac/token/generate  — Generate scoped API access token
 * - POST /api/v1/rbac/token/revoke    — Revoke active token
 * - GET  /api/v1/rbac/matrix          — Retrieve full role-permission capability matrix
 */
class Rbac extends BaseApiController
{
    private static ?TenantWorkspaceManager $tenantInstance = null;
    private static ?RolePermissionMatrix $matrixInstance = null;
    private static ?AttributeAccessControlEngine $abacInstance = null;
    private static ?ScopedApiTokenManager $tokenInstance = null;

    private function getTenantManager(): TenantWorkspaceManager
    {
        if (self::$tenantInstance === null) {
            self::$tenantInstance = new TenantWorkspaceManager();
        }
        return self::$tenantInstance;
    }

    private function getMatrix(): RolePermissionMatrix
    {
        if (self::$matrixInstance === null) {
            self::$matrixInstance = new RolePermissionMatrix();
        }
        return self::$matrixInstance;
    }

    private function getABAC(): AttributeAccessControlEngine
    {
        if (self::$abacInstance === null) {
            self::$abacInstance = new AttributeAccessControlEngine();
        }
        return self::$abacInstance;
    }

    private function getTokenManager(): ScopedApiTokenManager
    {
        if (self::$tokenInstance === null) {
            self::$tokenInstance = new ScopedApiTokenManager();
        }
        return self::$tokenInstance;
    }

    /**
     * POST /api/v1/rbac/tenant/create
     */
    public function createTenant()
    {
        $json = $this->request->getJSON(true) ?? [];
        $tenantId = $json['tenant_id'] ?? '';
        $name = $json['name'] ?? 'New Workspace';
        $ownerId = $json['owner_id'] ?? 'usr_' . bin2hex(random_bytes(4));
        $quotas = $json['quotas'] ?? [];

        try {
            $tenant = $this->getTenantManager()->createTenant($tenantId, $name, $ownerId, $quotas);
            return $this->respondSuccess($tenant, 'Tenant workspace provisioned');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/rbac/check
     */
    public function check()
    {
        $json = $this->request->getJSON(true) ?? [];
        $role = $json['role'] ?? 'MEMBER';
        $permission = $json['permission'] ?? 'repo:read';

        $hasRbac = $this->getMatrix()->hasPermission($role, $permission);

        $subject = $json['subject'] ?? ['role' => $role, 'mfa_enabled' => true];
        $resource = $json['resource'] ?? ['classification' => 'INTERNAL'];
        $abac = $this->getABAC()->evaluate($subject, $permission, $resource);

        $isAllowed = $hasRbac && $abac['allowed'];

        return $this->respondSuccess([
            'allowed'     => $isAllowed,
            'rbac_grant'  => $hasRbac,
            'abac_grant'  => $abac['allowed'],
            'abac_reason' => $abac['reason'],
        ], $isAllowed ? 'Access authorized' : 'Access denied');
    }

    /**
     * POST /api/v1/rbac/token/generate
     */
    public function generateToken()
    {
        $json = $this->request->getJSON(true) ?? [];
        $userId = $json['user_id'] ?? 'usr_demo';
        $tenantId = $json['tenant_id'] ?? 'default';
        $scopes = $json['scopes'] ?? ['repo:read'];
        $ttl = (int)($json['ttl'] ?? 3600);

        $token = $this->getTokenManager()->generateToken($userId, $tenantId, $scopes, $ttl);
        return $this->respondSuccess($token, 'Scoped API token generated');
    }

    /**
     * POST /api/v1/rbac/token/revoke
     */
    public function revokeToken()
    {
        $json = $this->request->getJSON(true) ?? [];
        $tokenId = $json['token_id'] ?? '';
        if (empty($tokenId)) {
            return $this->respondError('token_id is required', 400);
        }

        $this->getTokenManager()->revokeToken($tokenId);
        return $this->respondSuccess(['revoked' => true, 'token_id' => $tokenId], 'Token revoked successfully');
    }

    /**
     * GET /api/v1/rbac/matrix
     */
    public function matrix()
    {
        return $this->respondSuccess([
            'matrix'  => $this->getMatrix()->getMatrix(),
            'tenants' => $this->getTenantManager()->listTenants(),
        ], 'Role permission matrix retrieved');
    }
}
