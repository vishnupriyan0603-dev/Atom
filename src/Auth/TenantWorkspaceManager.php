<?php

namespace Atom\Auth;

use Atom\Security\SecretRedactor;

/**
 * Tenant Workspace Manager — Phase 36
 *
 * Multi-tenant workspace partitioning, quota isolation, and active context switching.
 */
class TenantWorkspaceManager
{
    private array $tenants = [];
    private string $activeTenantId = 'default';
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();

        // Seed default root tenant
        $this->tenants['default'] = [
            'id'             => 'default',
            'name'           => 'Primary Organization Workspace',
            'owner_id'       => 'usr_owner_root',
            'storage_cap_mb' => 10240, // 10 GB
            'storage_used_mb'=> 128,
            'compute_units'  => 10000,
            'rate_limit_rpm' => 600,
            'members'        => ['usr_owner_root' => 'OWNER'],
            'created_at'     => time(),
        ];
    }

    /**
     * Provisions a new isolated tenant workspace.
     */
    public function createTenant(string $tenantId, string $name, string $ownerId, array $quotas = []): array
    {
        $tenantId = strtolower(trim($tenantId));
        if (empty($tenantId) || preg_match('/[^a-z0-9_-]/', $tenantId)) {
            throw new \InvalidArgumentException("Invalid tenant identifier '{$tenantId}'");
        }

        if (isset($this->tenants[$tenantId])) {
            throw new \RuntimeException("Tenant workspace '{$tenantId}' already exists");
        }

        $tenant = [
            'id'             => $tenantId,
            'name'           => $this->redactor->redact($name),
            'owner_id'       => $ownerId,
            'storage_cap_mb' => (int)($quotas['storage_cap_mb'] ?? 5120),
            'storage_used_mb'=> 0,
            'compute_units'  => (int)($quotas['compute_units'] ?? 5000),
            'rate_limit_rpm' => (int)($quotas['rate_limit_rpm'] ?? 300),
            'members'        => [$ownerId => 'OWNER'],
            'created_at'     => time(),
        ];

        $this->tenants[$tenantId] = $tenant;
        return $tenant;
    }

    /**
     * Gets tenant by ID.
     */
    public function getTenant(string $tenantId): array
    {
        $tenantId = strtolower(trim($tenantId));
        if (!isset($this->tenants[$tenantId])) {
            throw new \InvalidArgumentException("Tenant workspace '{$tenantId}' not found");
        }
        return $this->tenants[$tenantId];
    }

    /**
     * Lists all registered tenants.
     */
    public function listTenants(): array
    {
        return array_values($this->tenants);
    }

    /**
     * Switches active tenant context.
     */
    public function setActiveTenant(string $tenantId): void
    {
        $tenantId = strtolower(trim($tenantId));
        if (!isset($this->tenants[$tenantId])) {
            throw new \InvalidArgumentException("Cannot switch to non-existent tenant '{$tenantId}'");
        }
        $this->activeTenantId = $tenantId;
    }

    /**
     * Gets active tenant workspace context.
     */
    public function getActiveTenant(): array
    {
        return $this->tenants[$this->activeTenantId];
    }

    /**
     * Adds member to a tenant workspace.
     */
    public function addMember(string $tenantId, string $userId, string $role = 'MEMBER'): void
    {
        $tenantId = strtolower(trim($tenantId));
        if (!isset($this->tenants[$tenantId])) {
            throw new \InvalidArgumentException("Tenant workspace '{$tenantId}' not found");
        }
        $this->tenants[$tenantId]['members'][$userId] = strtoupper($role);
    }
}
