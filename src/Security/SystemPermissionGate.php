<?php

namespace Atom\Security;

/**
 * Phase 110: Full System Access & Interactive User Permission Gate
 *
 * Enforces granular risk classification and explicit human-in-the-loop authorization
 * before ATOM executes elevated, modifying, or destructive OS operations.
 */
class SystemPermissionGate
{
    public const LEVEL_0_READ_ONLY        = 0; // Info, telemetry, status, port inspection (Autonomous)
    public const LEVEL_1_SAFE_INTERACTION  = 1; // Launch Chrome/Notepad, focus window, type in approved app (Autonomous)
    public const LEVEL_2_SENSITIVE_WRITE   = 2; // Write file outside workspace, edit environment variables (Permission Required)
    public const LEVEL_3_CRITICAL_SYSTEM   = 3; // Kill processes, restart services, raw shell execution (Explicit Permission Required)

    private array $sessionPermissions = [];
    private array $pendingRequests = [];
    private array $auditTrail = [];
    public function __construct(bool $fullAccessBypass = false)
    {
        // Kept for constructor compatibility. Full-access bypass is disabled.
    }

    /**
     * Determine the risk level of an intended action.
     */
    public function getRiskLevel(string $action, array $params = []): int
    {
        $action = strtolower(trim($action));

        // Critical System Actions (Level 3)
        $critical = ['kill_process', 'kill', 'restart_service', 'stop_service', 'shell_exec', 'execute_shell', 'format', 'shutdown'];
        if (in_array($action, $critical, true)) {
            return self::LEVEL_3_CRITICAL_SYSTEM;
        }

        // Sensitive Write Actions (Level 2)
        $sensitive = ['write_system_file', 'delete_file', 'modify_env', 'set_env', 'install_package', 'modify_registry'];
        if (in_array($action, $sensitive, true)) {
            return self::LEVEL_2_SENSITIVE_WRITE;
        }

        // Safe Desktop Interactions (Level 1)
        $safeDesktop = ['open_application', 'open_url', 'youtube', 'search_and_play_youtube', 'focus_window', 'type_text', 'press_key', 'milestone_1'];
        if (in_array($action, $safeDesktop, true)) {
            return self::LEVEL_1_SAFE_INTERACTION;
        }

        // Read-only / Telemetry (Level 0)
        return self::LEVEL_0_READ_ONLY;
    }

    /**
     * Check whether an action requires explicit human permission.
     */
    public function requiresPermission(string $action, array $params = []): bool
    {
        // Every system action, including reads, requires a fresh human approval.
        return true;
    }

    /**
     * Request permission for an action.
     * Returns structured authorization payload.
     */
    public function createPermissionRequest(string $action, string $description, string $target, array $params = []): array
    {
        $requestId = 'PERM-' . strtoupper(bin2hex(random_bytes(4)));
        $level = $this->getRiskLevel($action, $params);
        $levelName = match ($level) {
            self::LEVEL_3_CRITICAL_SYSTEM => 'CRITICAL_SYSTEM_ACTION',
            self::LEVEL_2_SENSITIVE_WRITE => 'SENSITIVE_WRITE',
            self::LEVEL_1_SAFE_INTERACTION => 'SAFE_INTERACTION',
            default => 'READ_ONLY',
        };

        $request = [
            'request_id'   => $requestId,
            'action'       => $action,
            'description'  => $description,
            'target'       => $target,
            'risk_level'   => $level,
            'level_name'   => $levelName,
            'params'       => $params,
            'status'       => 'PENDING',
            'created_at'   => date('c'),
        ];

        $this->pendingRequests[$requestId] = $request;

        $this->logAudit([
            'event'      => 'PERMISSION_REQUESTED',
            'request_id' => $requestId,
            'action'     => $action,
            'target'     => $target,
            'level'      => $levelName,
        ]);

        return $request;
    }

    /**
     * Resolve a permission request for this action only (APPROVE / REJECT).
     */
    public function resolvePermission(string $requestId, string $decision, string $resolvedBy = 'OWNER'): array
    {
        if (!isset($this->pendingRequests[$requestId])) {
            return [
                'success' => false,
                'error'   => "Permission request '{$requestId}' not found or already resolved.",
            ];
        }

        $req = $this->pendingRequests[$requestId];
        $cleanDecision = strtoupper(trim($decision));

        if (in_array($cleanDecision, ['YES', 'APPROVE', 'GRANTED', '1', 'TRUE'], true)) {
            $req['status'] = 'GRANTED';
        } elseif (in_array($cleanDecision, ['ALLOW_SESSION', 'SESSION', 'ALWAYS'], true)) {
            $req['status'] = 'REJECTED';
        } else {
            $req['status'] = 'REJECTED';
        }

        $req['resolved_at'] = date('c');
        $req['resolved_by'] = $resolvedBy;

        unset($this->pendingRequests[$requestId]);

        $this->logAudit([
            'event'      => 'PERMISSION_RESOLVED',
            'request_id' => $requestId,
            'action'     => $req['action'],
            'status'     => $req['status'],
            'resolved_by'=> $resolvedBy,
        ]);

        return [
            'success'   => true,
            'request'   => $req,
            'is_granted'=> $req['status'] === 'GRANTED',
        ];
    }

    /**
     * Deprecated compatibility method. Session-wide permission is disabled.
     */
    public function grantSessionPermission(string $category = 'all'): void
    {
        $this->logAudit([
            'event'    => 'SESSION_PERMISSION_BLOCKED',
            'category' => $category,
        ]);
    }

    /**
     * Check if session permission is active.
     */
    public function hasSessionPermission(string $action): bool
    {
        return false;
    }

    /**
     * Revoke all session permissions.
     */
    public function revokePermissions(): void
    {
        $this->sessionPermissions = [];
        $this->pendingRequests = [];

        $this->logAudit([
            'event' => 'ALL_PERMISSIONS_REVOKED',
        ]);
    }

    /**
     * Get active permission status.
     */
    public function getStatus(): array
    {
        return [
            'gate'               => 'SystemPermissionGate (Phase 110)',
            'full_access_bypass' => false,
            'active_session_grants' => [],
            'pending_requests'   => array_values($this->pendingRequests),
            'audit_log_count'    => count($this->auditTrail),
            'timestamp'          => date('c'),
        ];
    }

    public function getAuditTrail(): array
    {
        return $this->auditTrail;
    }

    private function logAudit(array $entry): void
    {
        $entry['timestamp'] = date('c');
        $this->auditTrail[] = $entry;
    }
}

