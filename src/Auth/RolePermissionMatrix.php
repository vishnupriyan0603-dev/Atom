<?php

namespace Atom\Auth;

/**
 * Role Permission Matrix — Phase 36
 *
 * Enterprise hierarchical Role-Based Access Control (RBAC) matrix
 * with permission inheritance and wildcard evaluation.
 */
class RolePermissionMatrix
{
    public const ROLE_OWNER           = 'OWNER';
    public const ROLE_ADMIN           = 'ADMIN';
    public const ROLE_MEMBER          = 'MEMBER';
    public const ROLE_AUDITOR         = 'AUDITOR';
    public const ROLE_SERVICE_ACCOUNT = 'SERVICE_ACCOUNT';

    private array $rolePermissions = [
        self::ROLE_OWNER => [
            '*', // Unrestricted superuser capability
        ],
        self::ROLE_ADMIN => [
            'repo:read',
            'repo:write',
            'vault:decrypt',
            'swarm:dispatch',
            'plugin:install',
            'plugin:toggle',
            'refactor:execute',
            'voice:stream',
            'admin:manage_users',
            'audit:view',
        ],
        self::ROLE_MEMBER => [
            'repo:read',
            'repo:write',
            'swarm:dispatch',
            'refactor:execute',
            'voice:stream',
        ],
        self::ROLE_AUDITOR => [
            'repo:read',
            'audit:view',
            'vault:merkle_inspect',
            'telemetry:view',
        ],
        self::ROLE_SERVICE_ACCOUNT => [
            'repo:read',
            'swarm:dispatch',
            'telemetry:push',
        ],
    ];

    /**
     * Checks whether a role has permission for a specific capability.
     *
     * @param string $role Role name (OWNER, ADMIN, MEMBER, AUDITOR, SERVICE_ACCOUNT).
     * @param string $permission Required permission string (e.g. 'repo:read', 'vault:decrypt').
     * @return bool True if permitted, false otherwise.
     */
    public function hasPermission(string $role, string $permission): bool
    {
        $role = strtoupper(trim($role));
        $permission = strtolower(trim($permission));

        if (!isset($this->rolePermissions[$role])) {
            return false;
        }

        $granted = $this->rolePermissions[$role];

        // 1. Full wildcard check (OWNER)
        if (in_array('*', $granted, true)) {
            return true;
        }

        // 2. Direct exact permission match
        if (in_array($permission, $granted, true)) {
            return true;
        }

        // 3. Domain wildcard match (e.g. 'repo:*' covers 'repo:read')
        $parts = explode(':', $permission);
        if (count($parts) === 2) {
            $domainWildcard = $parts[0] . ':*';
            if (in_array($domainWildcard, $granted, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns full role permission definition table.
     */
    public function getMatrix(): array
    {
        return $this->rolePermissions;
    }
}
