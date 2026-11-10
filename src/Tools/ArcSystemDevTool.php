<?php

namespace Atom\Tools;

use Atom\Arc\ArcSystemDevController;

/**
 * Tool representation of ARC THREE Developer System & OS Controller.
 */
class ArcSystemDevTool implements ToolInterface
{
    private ArcSystemDevController $controller;

    public function __construct(?ArcSystemDevController $controller = null)
    {
        $this->controller = $controller ?? new ArcSystemDevController();
    }

    public function getName(): string
    {
        return 'arc_system';
    }

    public function execute(array $input): array
    {
        $action = strtolower((string)($input['action'] ?? 'ports'));

        switch ($action) {
            case 'ports':
            case 'listening_ports':
                return $this->controller->getListeningPorts();

            case 'check_port':
            case 'port':
                return $this->controller->checkPortAvailability((int)($input['port'] ?? 80));

            case 'processes':
            case 'list_processes':
            case 'ps':
                return $this->controller->listProcesses(
                    isset($input['filter']) ? (string)$input['filter'] : null,
                    (int)($input['limit'] ?? 50)
                );

            case 'process_details':
            case 'process':
                return $this->controller->getProcessDetails($input['pid'] ?? $input['name'] ?? 0);

            case 'kill_process':
            case 'kill':
                return $this->controller->killProcess(
                    $input['pid'] ?? $input['name'] ?? $input['target'] ?? 0,
                    (bool)($input['force'] ?? false)
                );

            case 'toolchain':
            case 'tools':
            case 'runtimes':
                return $this->controller->detectDeveloperToolchain();

            case 'service':
            case 'service_status':
                return $this->controller->getServiceStatus((string)($input['service'] ?? $input['name'] ?? ''));

            case 'restart_service':
                return $this->controller->restartService((string)($input['service'] ?? $input['name'] ?? ''));

            case 'execute_shell':
            case 'shell':
            case 'command':
                return $this->controller->executeShellCommand((string)($input['command'] ?? $input['cmd'] ?? ''));

            case 'write_system_file':
            case 'write_file':
                return $this->controller->writeSystemFile(
                    (string)($input['file'] ?? $input['path'] ?? ''),
                    (string)($input['content'] ?? '')
                );

            case 'telemetry':
            case 'hardware':
            case 'stats':
                return $this->controller->getHardwareTelemetry();

            case 'env':
            case 'environment':
                return $this->controller->getEnvironmentVariables(isset($input['key']) ? (string)$input['key'] : null);

            case 'locked':
            case 'find_lock':
                return $this->controller->findProcessLockingFile((string)($input['file'] ?? $input['path'] ?? ''));

            case 'permission_status':
                return $this->controller->getPermissionGate()->getStatus();

            case 'grant_session':
                $this->controller->getPermissionGate()->grantSessionPermission((string)($input['category'] ?? 'all'));
                return ['success' => false, 'requires_human_gate' => true, 'message' => 'Session-wide grants are disabled; approve each action separately.'];

            case 'revoke_permissions':
                $this->controller->getPermissionGate()->revokePermissions();
                return ['success' => true, 'message' => 'All permissions revoked.'];

            default:
                return [
                    'success' => false,
                    'error' => "Unknown arc_system action '{$action}'. Supported: ports, check_port, processes, process_details, kill_process, restart_service, execute_shell, write_system_file, toolchain, service, telemetry, env, locked.",
                ];
        }
    }
}

