<?php

namespace Atom\Arc;

/**
 * ARC Master Controller / Orchestrator (Phases 104 - 109)
 *
 * Coordinates independent sub-controllers:
 * - ARC ONE: Local Git Repository Operations
 * - ARC TWO: Windows Desktop Application Automation
 * - ARC THREE: Advanced Developer System & Technical Control (Phase 109)
 *
 * Provides a unified dispatch API, workflow sequencer, and audit action logging.
 */
use Atom\Security\SystemPermissionGate;

class ArcMasterController
{
    private ArcOneGitController $arcOne;
    private ArcTwoDesktopController $arcTwo;
    private ArcSystemDevController $arcThree;
    private SystemPermissionGate $permissionGate;
    private array $auditLog = [];

    public function __construct(
        ?ArcOneGitController $arcOne = null,
        ?ArcTwoDesktopController $arcTwo = null,
        ?ArcSystemDevController $arcThree = null,
        ?SystemPermissionGate $permissionGate = null
    ) {
        $this->permissionGate = $permissionGate ?? new SystemPermissionGate();
        $this->arcOne = $arcOne ?? new ArcOneGitController();
        $this->arcTwo = $arcTwo ?? new ArcTwoDesktopController();
        $this->arcThree = $arcThree ?? new ArcSystemDevController($this->permissionGate);
    }

    public function getPermissionGate(): SystemPermissionGate
    {
        return $this->permissionGate;
    }

    public function arcOne(): ArcOneGitController
    {
        return $this->arcOne;
    }

    public function arcTwo(): ArcTwoDesktopController
    {
        return $this->arcTwo;
    }

    public function arcThree(): ArcSystemDevController
    {
        return $this->arcThree;
    }

    public function getAuditLog(): array
    {
        return $this->auditLog;
    }

    /**
     * Dispatch an action to ARC ONE, ARC TWO, or ARC THREE via unified dot-notation or explicit arguments.
     *
     * Examples:
     * - dispatch('git.status')
     * - dispatch('desktop.open_application', ['app' => 'notepad'])
     * - dispatch('sys.ports')
     * - dispatch('sys.processes', ['filter' => 'php'])
     * - dispatch('sys.toolchain')
     * - dispatch('sys.telemetry')
     */
    public function dispatch(string $targetOrCommand, array|string $actionOrParams = [], array $params = []): array
    {
        $target = $targetOrCommand;
        $action = is_string($actionOrParams) ? $actionOrParams : '';
        $actualParams = is_array($actionOrParams) ? $actionOrParams : $params;

        // Support dot notation: "git.status", "desktop.type_text", "sys.processes", "dev.toolchain"
        if (str_contains($targetOrCommand, '.')) {
            $parts = explode('.', $targetOrCommand, 2);
            $prefix = strtolower($parts[0]);
            $action = $parts[1];

            if (in_array($prefix, ['git', 'arc_one', 'arcone'])) {
                $target = 'arc_one';
            } elseif (in_array($prefix, ['desktop', 'arc_two', 'arctwo'])) {
                $target = 'arc_two';
            } elseif (in_array($prefix, ['sys', 'system', 'dev', 'arc_three', 'arcthree'])) {
                $target = 'arc_three';
            }
        }

        $target = strtolower($target);
        $result = [];

        if ($target === 'arc_one' || $target === 'git') {
            $result = $this->dispatchArcOne($action, $actualParams);
        } elseif ($target === 'arc_two' || $target === 'desktop') {
            $result = $this->dispatchArcTwo($action, $actualParams);
        } elseif ($target === 'arc_three' || $target === 'sys' || $target === 'dev' || $target === 'system') {
            $result = $this->dispatchArcThree($action, $actualParams);
        } else {
            $result = [
                'success' => false,
                'error' => "Unknown ARC component target '{$target}'. Supported: arc_one (git), arc_two (desktop), arc_three (sys/dev).",
            ];
        }

        $result['dispatched_at'] = date('c');
        $this->auditLog[] = [
            'target' => $target,
            'action' => $action,
            'params' => $actualParams,
            'result' => $result,
            'timestamp' => date('c'),
        ];

        return $result;
    }

    /**
     * Dispatch action to ARC ONE (Git).
     */
    private function dispatchArcOne(string $action, array $params): array
    {
        switch ($action) {
            case 'is_repo':
            case 'is_git_repository':
                return $this->arcOne->isGitRepository();

            case 'status':
                return $this->arcOne->status();

            case 'changed_files':
            case 'changes':
                return $this->arcOne->changedFiles();

            case 'diff':
                return $this->arcOne->diff(
                    (bool)($params['staged'] ?? false),
                    isset($params['file']) ? (string)$params['file'] : null
                );

            case 'stage':
            case 'add':
                return $this->arcOne->stage($params['files'] ?? '.');

            case 'commit':
                return $this->arcOne->commit((string)($params['message'] ?? ''));

            default:
                return [
                    'success' => false,
                    'error' => "Unknown ARC ONE action '{$action}'. Supported: status, changed_files, diff, stage, commit.",
                ];
        }
    }

    /**
     * Dispatch action to ARC TWO (Desktop).
     */
    private function dispatchArcTwo(string $action, array $params): array
    {
        switch ($action) {
            case 'open_application':
            case 'open':
                return $this->arcTwo->openApplication(
                    (string)($params['app'] ?? 'notepad'),
                    (array)($params['args'] ?? [])
                );

            case 'focus_window':
            case 'focus':
                return $this->arcTwo->focusWindow((string)($params['target'] ?? $params['title'] ?? 'notepad'));

            case 'type_text':
            case 'type':
                return $this->arcTwo->typeText(
                    (string)($params['text'] ?? ''),
                    (string)($params['target'] ?? 'notepad')
                );

            case 'press_key':
                return $this->arcTwo->pressKey(
                    (string)($params['key'] ?? '{ENTER}'),
                    (string)($params['target'] ?? 'notepad')
                );

            case 'close_application':
            case 'close':
                return $this->arcTwo->closeApplication(
                    (string)($params['process'] ?? $params['app'] ?? 'notepad'),
                    (bool)($params['force'] ?? false)
                );

            case 'milestone_1':
            case 'milestone1':
                return $this->arcTwo->runMilestoneOne(
                    (string)($params['text'] ?? 'Hi Vichu')
                );

            default:
                return [
                    'success' => false,
                    'error' => "Unknown ARC TWO action '{$action}'. Supported: open_application, focus_window, type_text, press_key, close_application, milestone_1.",
                ];
        }
    }

    /**
     * Dispatch action to ARC THREE (Advanced Developer & OS System Controller).
     */
    private function dispatchArcThree(string $action, array $params): array
    {
        switch ($action) {
            case 'processes':
            case 'list_processes':
            case 'ps':
                return $this->arcThree->listProcesses(
                    isset($params['filter']) ? (string)$params['filter'] : null,
                    (int)($params['limit'] ?? 50)
                );

            case 'process_details':
            case 'process':
                return $this->arcThree->getProcessDetails($params['pid'] ?? $params['name'] ?? 0);

            case 'kill_process':
            case 'kill':
                return $this->arcThree->killProcess(
                    $params['pid'] ?? $params['name'] ?? $params['target'] ?? 0,
                    (bool)($params['force'] ?? false)
                );

            case 'locked':
            case 'find_lock':
            case 'file_lock':
                return $this->arcThree->findProcessLockingFile((string)($params['file'] ?? $params['path'] ?? ''));

            case 'ports':
            case 'listening_ports':
            case 'netstat':
                return $this->arcThree->getListeningPorts();

            case 'check_port':
            case 'port':
                return $this->arcThree->checkPortAvailability((int)($params['port'] ?? 80));

            case 'toolchain':
            case 'tools':
            case 'runtimes':
                return $this->arcThree->detectDeveloperToolchain();

            case 'service':
            case 'service_status':
                return $this->arcThree->getServiceStatus((string)($params['service'] ?? $params['name'] ?? ''));

            case 'telemetry':
            case 'hardware':
            case 'stats':
                return $this->arcThree->getHardwareTelemetry();

            case 'env':
            case 'environment':
                return $this->arcThree->getEnvironmentVariables(isset($params['key']) ? (string)$params['key'] : null);

            default:
                return [
                    'success' => false,
                    'error' => "Unknown ARC THREE action '{$action}'. Supported: processes, process_details, kill_process, locked, ports, check_port, toolchain, service, telemetry, env.",
                ];
        }
    }

    /**
     * Get aggregated system health & state report across ARC One, ARC Two, and ARC Three.
     */
    public function getSystemState(): array
    {
        $gitStatus = $this->arcOne->status();
        return [
            'controller' => 'ARC Master Orchestrator',
            'version' => '1.1.0',
            'platform' => $this->arcTwo->getPlatform(),
            'arc_one' => [
                'name' => 'ARC ONE (Git Controller)',
                'repo_path' => $this->arcOne->getRepoPath(),
                'git_ready' => $gitStatus['success'] ?? false,
                'branch' => $gitStatus['branch'] ?? null,
                'clean' => $gitStatus['clean'] ?? null,
            ],
            'arc_two' => [
                'name' => 'ARC TWO (Desktop Controller)',
                'desktop_ready' => ($this->arcTwo->getPlatform() === 'Windows'),
            ],
            'arc_three' => [
                'name' => 'ARC THREE (Developer & System Controller - Phase 109)',
                'system_ready' => true,
            ],
            'total_dispatches' => count($this->auditLog),
            'timestamp' => date('c'),
        ];
    }
}
