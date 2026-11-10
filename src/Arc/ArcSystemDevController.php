<?php

namespace Atom\Arc;

/**
 * ARC THREE — Advanced Developer System & Technical Control Controller (Phase 109)
 *
 * Provides low-level technical developer and OS automation controls:
 * - Process management & inspection (PID, memory MB, CPU, threads, handles)
 * - Process termination and file-lock discovery
 * - Listening ports and socket connectivity diagnostics
 * - Developer toolchain detection (PHP, Node, .NET, Python, Composer, Git, Docker, MySQL, Apache)
 * - Windows background services monitoring & control
 * - Hardware compute & memory telemetry (CPU topology, RAM breakdown, disk partitions)
 * - Machine and process environment variable inspection
 */
use Atom\Security\SystemPermissionGate;

class ArcSystemDevController
{
    private string $platform;
    private array $actionLog = [];
    private SystemPermissionGate $permissionGate;

    public function __construct(?SystemPermissionGate $permissionGate = null)
    {
        $this->platform = PHP_OS_FAMILY;
        $this->permissionGate = $permissionGate ?? new SystemPermissionGate();
    }

    public function getPermissionGate(): SystemPermissionGate
    {
        return $this->permissionGate;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getActionLog(): array
    {
        return $this->actionLog;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. PROCESS & THREAD MANAGEMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * List running OS processes with memory, CPU, PID, and thread counts.
     */
    public function listProcesses(?string $filter = null, int $limit = 50): array
    {
        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'list_processes',
                'count' => 1,
                'processes' => [
                    ['pid' => 1000, 'name' => 'php', 'memory_mb' => 24.5, 'cpu_seconds' => 0.4, 'threads' => 4, 'handles' => 120]
                ],
                'simulated' => true,
            ]);
        }

        $filterScript = '';
        if ($filter !== null && trim($filter) !== '') {
            $cleanFilter = preg_replace('/[^a-zA-Z0-9_\-\.\*]/', '', trim($filter));
            $filterScript = " | Where-Object { \$_.ProcessName -like '*{$cleanFilter}*' }";
        }

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
Get-Process{$filterScript} | Select-Object -First {$limit} | ForEach-Object {
    [PSCustomObject]@{
        pid = \$_.Id
        name = \$_.ProcessName
        memory_mb = [math]::Round(\$_.WorkingSet64 / 1MB, 2)
        cpu_seconds = [math]::Round(\$_.CPU, 2)
        threads = \$_.Threads.Count
        handles = \$_.HandleCount
    }
} | ConvertTo-Json -Compress
POWERSHELL;

        $rawJson = trim($this->runPowerShell($psScript));
        $processes = [];

        if (!empty($rawJson)) {
            $decoded = json_decode($rawJson, true);
            if ($decoded !== null) {
                $processes = isset($decoded['pid']) ? [$decoded] : $decoded;
            }
        }

        return $this->logResult([
            'success' => true,
            'action' => 'list_processes',
            'filter' => $filter,
            'count' => count($processes),
            'processes' => $processes,
        ]);
    }

    /**
     * Get detailed process information including executable path and start time.
     */
    public function getProcessDetails(int|string $pidOrName): array
    {
        $clean = trim((string)$pidOrName);
        if ($clean === '') {
            return $this->logResult([
                'success' => false,
                'action' => 'get_process_details',
                'error' => 'Process ID or name cannot be empty.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'get_process_details',
                'process' => ['pid' => is_numeric($clean) ? (int)$clean : 1234, 'name' => (string)$clean, 'path' => '/usr/bin/php'],
                'simulated' => true,
            ]);
        }

        $isPid = is_numeric($clean);
        $lookupParam = $isPid ? "-Id {$clean}" : "-Name '" . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $clean) . "'";

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
\$p = Get-Process {$lookupParam} -ErrorAction SilentlyContinue | Select-Object -First 1
if (\$p) {
    [PSCustomObject]@{
        pid = \$p.Id
        name = \$p.ProcessName
        path = \$p.Path
        memory_mb = [math]::Round(\$p.WorkingSet64 / 1MB, 2)
        cpu_seconds = [math]::Round(\$p.CPU, 2)
        threads = \$p.Threads.Count
        handles = \$p.HandleCount
        start_time = if (\$p.StartTime) { \$p.StartTime.ToString("o") } else { \$null }
        responding = \$p.Responding
    } | ConvertTo-Json -Compress
} else {
    Write-Output "NOT_FOUND"
}
POWERSHELL;

        $rawJson = trim($this->runPowerShell($psScript));
        if ($rawJson === 'NOT_FOUND' || empty($rawJson)) {
            return $this->logResult([
                'success' => false,
                'action' => 'get_process_details',
                'error' => "Process '{$clean}' not found.",
            ]);
        }

        $data = json_decode($rawJson, true);
        return $this->logResult([
            'success' => true,
            'action' => 'get_process_details',
            'process' => $data ?: [],
        ]);
    }

    /**
     * Terminate a running process safely by PID or Process Name.
     */
    public function killProcess(int|string $pidOrName, bool $force = false): array
    {
        $clean = trim((string)$pidOrName);
        if ($clean === '') {
            return $this->logResult([
                'success' => false,
                'action' => 'kill_process',
                'error' => 'Invalid process identifier.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'kill_process',
                'target' => $clean,
                'simulated' => true,
                'message' => "Simulated kill process {$clean}",
            ]);
        }

        $isPid = is_numeric($clean);
        $lookupParam = $isPid ? "-Id {$clean}" : "-Name '" . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $clean) . "'";
        $forceParam = $force ? "-Force" : "";

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
Stop-Process {$lookupParam} {$forceParam} -ErrorAction SilentlyContinue
if (\$?) { Write-Output "KILLED" } else { Write-Output "FAILED" }
POWERSHELL;

        $res = trim($this->runPowerShell($psScript));

        return $this->logResult([
            'success' => ($res === 'KILLED'),
            'action' => 'kill_process',
            'target' => $clean,
            'force' => $force,
            'message' => ($res === 'KILLED') ? "Process {$clean} terminated successfully." : "Failed to terminate process {$clean} (process may not exist or requires elevation).",
        ]);
    }

    /**
     * Detect which process is holding an open file handle lock.
     */
    public function findProcessLockingFile(string $filePath): array
    {
        $cleanPath = trim(str_replace(['"', "'"], '', $filePath));
        if ($cleanPath === '') {
            return [
                'success' => false,
                'action' => 'find_process_locking_file',
                'error' => 'File path cannot be empty.',
            ];
        }

        $fullPath = realpath($cleanPath) ?: $cleanPath;

        if ($this->platform !== 'Windows') {
            return [
                'success' => true,
                'action' => 'find_process_locking_file',
                'file' => $fullPath,
                'locking_processes' => [],
                'is_locked' => false,
            ];
        }

        $safePath = escapeshellarg($fullPath);
        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
\$path = {$safePath}
\$locked = \$false
try {
    \$stream = [System.IO.File]::Open(\$path, [System.IO.FileMode]::Open, [System.IO.FileAccess]::ReadWrite, [System.IO.FileShare]::None)
    \$stream.Close()
    \$stream.Dispose()
} catch {
    \$locked = \$true
}
[PSCustomObject]@{
    path = \$path
    is_locked = \$locked
} | ConvertTo-Json -Compress
POWERSHELL;

        $rawJson = trim($this->runPowerShell($psScript));
        $data = json_decode($rawJson, true);

        return $this->logResult([
            'success' => true,
            'action' => 'find_process_locking_file',
            'file' => $fullPath,
            'is_locked' => $data['is_locked'] ?? false,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. NETWORK & LISTENING PORTS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Map all active listening TCP/UDP ports, PIDs, and process names.
     */
    public function getListeningPorts(): array
    {
        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'get_listening_ports',
                'count' => 3,
                'ports' => [
                    ['protocol' => 'TCP', 'port' => 80, 'local_address' => '0.0.0.0', 'pid' => 100, 'process_name' => 'httpd'],
                    ['protocol' => 'TCP', 'port' => 3306, 'local_address' => '127.0.0.1', 'pid' => 101, 'process_name' => 'mysqld'],
                    ['protocol' => 'TCP', 'port' => 8080, 'local_address' => '0.0.0.0', 'pid' => 102, 'process_name' => 'php'],
                ],
                'simulated' => true,
            ]);
        }

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue | Select-Object -Property LocalAddress, LocalPort, OwningProcess | ForEach-Object {
    \$pName = (Get-Process -Id \$_.OwningProcess -ErrorAction SilentlyContinue).ProcessName
    [PSCustomObject]@{
        protocol = "TCP"
        local_address = \$_.LocalAddress
        port = \$_.LocalPort
        pid = \$_.OwningProcess
        process_name = if (\$pName) { \$pName } else { "System" }
    }
} | ConvertTo-Json -Compress
POWERSHELL;

        $rawJson = trim($this->runPowerShell($psScript));
        $ports = [];

        if (!empty($rawJson)) {
            $decoded = json_decode($rawJson, true);
            if ($decoded !== null) {
                $ports = isset($decoded['port']) ? [$decoded] : $decoded;
            }
        }

        return $this->logResult([
            'success' => true,
            'action' => 'get_listening_ports',
            'count' => count($ports),
            'ports' => $ports,
        ]);
    }

    /**
     * Check if a specific network port is available or occupied.
     */
    public function checkPortAvailability(int $port): array
    {
        if ($port < 1 || $port > 65535) {
            return [
                'success' => false,
                'action' => 'check_port_availability',
                'port' => $port,
                'error' => 'Port number must be between 1 and 65535.',
            ];
        }

        $allPorts = $this->getListeningPorts();
        $occupiedBy = null;

        foreach ($allPorts['ports'] as $item) {
            if ((int)$item['port'] === $port) {
                $occupiedBy = $item;
                break;
            }
        }

        return $this->logResult([
            'success' => true,
            'action' => 'check_port_availability',
            'port' => $port,
            'is_available' => ($occupiedBy === null),
            'occupied_by' => $occupiedBy,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. DEVELOPER TOOLCHAIN & RUNTIMES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Detect developer runtimes, binaries, and version outputs across the machine.
     */
    public function detectDeveloperToolchain(): array
    {
        $toolchain = [];
        $tools = [
            'php' => ['cmd' => 'php -v', 'pattern' => '/PHP\s+([\d\.]+)/'],
            'composer' => ['cmd' => 'composer --version', 'pattern' => '/Composer\s+(?:version\s+)?([\d\.]+)/'],
            'node' => ['cmd' => 'node -v', 'pattern' => '/v([\d\.]+)/'],
            'npm' => ['cmd' => 'npm -v', 'pattern' => '/([\d\.]+)/'],
            'dotnet' => ['cmd' => 'dotnet --version', 'pattern' => '/([\d\.]+)/'],
            'python' => ['cmd' => 'python --version', 'pattern' => '/Python\s+([\d\.]+)/'],
            'git' => ['cmd' => 'git --version', 'pattern' => '/git\s+version\s+([\d\.\w]+)/'],
            'docker' => ['cmd' => 'docker --version', 'pattern' => '/Docker\s+version\s+([\d\.]+)/'],
        ];

        foreach ($tools as $name => $cfg) {
            $output = $this->runCliCommand($cfg['cmd']);
            $installed = ($output['exit_code'] === 0);
            $version = 'unknown';

            if ($installed && preg_match($cfg['pattern'], $output['stdout'], $m)) {
                $version = $m[1];
            }

            $toolchain[$name] = [
                'installed' => $installed,
                'version' => $version,
                'raw_output' => trim($output['stdout']),
            ];
        }

        return $this->logResult([
            'success' => true,
            'action' => 'detect_developer_toolchain',
            'toolchain' => $toolchain,
        ]);
    }

    /**
     * Check status of a Windows background service (e.g. Apache2.4, mysql, Docker).
     */
    public function getServiceStatus(string $serviceName): array
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', trim($serviceName));
        if (empty($safeName)) {
            return $this->logResult([
                'success' => false,
                'action' => 'get_service_status',
                'error' => 'Invalid service name.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'get_service_status',
                'service' => ['name' => $safeName, 'status' => 'Running', 'start_type' => 'Automatic'],
                'simulated' => true,
            ]);
        }

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
\$svc = Get-Service -Name "{$safeName}" -ErrorAction SilentlyContinue
if (\$svc) {
    [PSCustomObject]@{
        name = \$svc.Name
        display_name = \$svc.DisplayName
        status = \$svc.Status.ToString()
        start_type = \$svc.StartType.ToString()
    } | ConvertTo-Json -Compress
} else {
    Write-Output "NOT_FOUND"
}
POWERSHELL;

        $rawJson = trim($this->runPowerShell($psScript));
        if ($rawJson === 'NOT_FOUND' || empty($rawJson)) {
            return $this->logResult([
                'success' => false,
                'action' => 'get_service_status',
                'service_name' => $safeName,
                'error' => "Service '{$safeName}' not found.",
            ]);
        }

        return $this->logResult([
            'success' => true,
            'action' => 'get_service_status',
            'service' => json_decode($rawJson, true) ?: [],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. HARDWARE COMPUTE & TELEMETRY
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retrieve hardware compute topology, RAM breakdown, and storage volumes.
     */
    public function getHardwareTelemetry(): array
    {
        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'get_hardware_telemetry',
                'available' => false,
                'telemetry' => ['cpu' => null, 'ram' => null, 'disks' => []],
                'cpu' => null,
                'ram' => null,
                'disks' => [],
            ]);
        }

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
\$cpu = Get-CimInstance Win32_Processor | Select-Object -First 1
\$os = Get-CimInstance Win32_OperatingSystem
\$disks = Get-CimInstance Win32_LogicalDisk -Filter "DriveType=3" | ForEach-Object {
    [PSCustomObject]@{
        drive = \$_.DeviceID
        total_gb = [math]::Round(\$_.Size / 1GB, 2)
        free_gb = [math]::Round(\$_.FreeSpace / 1GB, 2)
        used_percent = [math]::Round(((\$_.Size - \$_.FreeSpace) / \$_.Size) * 100, 1)
    }
}

[PSCustomObject]@{
    cpu = [PSCustomObject]@{
        name = \$cpu.Name
        cores_logical = \$cpu.NumberOfLogicalProcessors
        cores_physical = \$cpu.NumberOfCores
        max_clock_mhz = \$cpu.MaxClockSpeed
    }
    ram = [PSCustomObject]@{
        total_gb = [math]::Round(\$os.TotalVisibleMemorySize / 1MB, 2)
        free_gb = [math]::Round(\$os.FreePhysicalMemory / 1MB, 2)
        used_gb = [math]::Round((\$os.TotalVisibleMemorySize - \$os.FreePhysicalMemory) / 1MB, 2)
        used_percent = [math]::Round(((\$os.TotalVisibleMemorySize - \$os.FreePhysicalMemory) / \$os.TotalVisibleMemorySize) * 100, 1)
    }
    disks = \$disks
} | ConvertTo-Json -Compress
POWERSHELL;

        $rawJson = trim($this->runPowerShell($psScript));
        $data = json_decode($rawJson, true);
        $available = is_array($data)
            && isset($data['cpu'], $data['ram'], $data['disks'])
            && is_array($data['cpu'])
            && is_array($data['ram'])
            && is_array($data['disks']);
        if (!$available) {
            $data = ['cpu' => null, 'ram' => null, 'disks' => []];
        }

        return $this->logResult([
            'success' => true,
            'action' => 'get_hardware_telemetry',
            'telemetry' => $data,
            'available' => $available,
        ]);
    }

    /**
     * Inspect system or process environment variables.
     */
    public function getEnvironmentVariables(?string $key = null): array
    {
        if ($key !== null) {
            $val = getenv($key);
            return $this->logResult([
                'success' => true,
                'action' => 'get_environment_variables',
                'key' => $key,
                'value' => $val !== false ? $val : null,
            ]);
        }

        $vars = getenv();
        return $this->logResult([
            'success' => true,
            'action' => 'get_environment_variables',
            'count' => count($vars),
            'variables' => $vars,
        ]);
    }

    /**
     * Restart a Windows background service.
     */
    public function restartService(string $serviceName): array
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', trim($serviceName));
        if (empty($safeName)) {
            return $this->logResult([
                'success' => false,
                'action' => 'restart_service',
                'error' => 'Invalid service name.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'restart_service',
                'service' => $safeName,
                'simulated' => true,
                'message' => "Simulated restart of service {$safeName}",
            ]);
        }

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
Restart-Service -Name "{$safeName}" -ErrorAction SilentlyContinue
if (\$?) { Write-Output "RESTARTED" } else { Write-Output "FAILED" }
POWERSHELL;

        $res = trim($this->runPowerShell($psScript));

        return $this->logResult([
            'success' => ($res === 'RESTARTED'),
            'action' => 'restart_service',
            'service' => $safeName,
            'message' => ($res === 'RESTARTED') ? "Service '{$safeName}' restarted successfully." : "Failed to restart service '{$safeName}' (check administrator elevation).",
        ]);
    }

    /**
     * Execute a shell command across the OS with stdout/stderr capture.
     */
    public function executeShellCommand(string $command, bool $asAdmin = false): array
    {
        $trim = trim($command);
        if ($trim === '') {
            return [
                'success' => false,
                'action' => 'execute_shell_command',
                'error' => 'Command cannot be empty.',
            ];
        }

        $res = $this->runCliCommand($trim);

        return $this->logResult([
            'success' => ($res['exit_code'] === 0),
            'action' => 'execute_shell_command',
            'command' => $trim,
            'exit_code' => $res['exit_code'],
            'stdout' => $res['stdout'],
            'stderr' => $res['stderr'],
        ]);
    }

    /**
     * Write an arbitrary file anywhere on the system (Full System Access).
     */
    public function writeSystemFile(string $filePath, string $content): array
    {
        $cleanPath = trim(str_replace(['"', "'"], '', $filePath));
        if ($cleanPath === '') {
            return [
                'success' => false,
                'action' => 'write_system_file',
                'error' => 'Target file path cannot be empty.',
            ];
        }

        $dir = dirname($cleanPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $written = @file_put_contents($cleanPath, $content);

        return $this->logResult([
            'success' => ($written !== false),
            'action' => 'write_system_file',
            'file' => $cleanPath,
            'bytes_written' => $written !== false ? $written : 0,
            'message' => ($written !== false) ? "Successfully wrote {$written} bytes to {$cleanPath}" : "Failed to write file {$cleanPath}",
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INTERNAL HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function runCliCommand(string $cmd): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return ['exit_code' => -1, 'stdout' => '', 'stderr' => 'Failed to spawn process'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => $stdout ?: '',
            'stderr' => $stderr ?: '',
        ];
    }

    private function runPowerShell(string $script): string
    {
        $encodedCmd = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));
        $cmd = "powershell.exe -NoProfile -NonInteractive -EncodedCommand {$encodedCmd}";

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($process)) {
            return '';
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($process);

        $clean = preg_replace('/#<\s*CLIXML[\s\S]*?<\/Objs>/', '', $stdout);
        return trim($clean);
    }

    private function logResult(array $result): array
    {
        $result['timestamp'] = date('c');
        $this->actionLog[] = $result;
        return $result;
    }
}

