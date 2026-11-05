<?php

namespace Atom\Arc;

/**
 * ARC TWO — Desktop / Atom Controller
 *
 * Responsible for controlling local Windows desktop applications:
 * - Application lifecycle (open, detect, close)
 * - Window detection & focus management
 * - Text input typing & key press simulation
 * - Structured success & error reporting
 */
class ArcTwoDesktopController
{
    private string $platform;
    private array $actionLog = [];

    public function __construct()
    {
        $this->platform = PHP_OS_FAMILY;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getActionLog(): array
    {
        return $this->actionLog;
    }

    /**
     * Launch an application on Windows and verify its process.
     */
    public function openApplication(string $app = 'notepad', array $args = []): array
    {
        $safeApp = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $app);
        if (empty($safeApp)) {
            return $this->logResult([
                'success' => false,
                'action' => 'open_application',
                'app' => $app,
                'error' => 'Invalid application name specified.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'open_application',
                'app' => $safeApp,
                'simulated' => true,
                'platform' => $this->platform,
                'message' => "Simulated application launch for non-Windows environment: {$safeApp}",
            ]);
        }

        $argList = '';
        if (!empty($args)) {
            $formattedArgs = array_map(function($a) {
                return "'" . str_replace("'", "''", $a) . "'";
            }, $args);
            $argList = '-ArgumentList ' . implode(', ', $formattedArgs);
        }

        // 1. Clean up any invisible headless instances with MainWindowHandle == 0
        $cleanupScript = "Get-Process -Name '{$safeApp}' -ErrorAction SilentlyContinue | Where-Object { \$_.MainWindowHandle -eq 0 } | Stop-Process -Force -ErrorAction SilentlyContinue";
        $this->runPowerShell($cleanupScript);

        // 2. Guarantee interactive user desktop attachment using Windows ShellExecute (cmd start & Shell.Application COM)
        pclose(popen("cmd.exe /c start {$safeApp}.exe", "r"));

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
try {
    \$sh = New-Object -ComObject Shell.Application
    \$sh.ShellExecute("{$safeApp}.exe", "", "", "open", 1)
} catch {}
POWERSHELL;
        $this->runPowerShell($psScript);

        // Wait and verify real visible window appears (MainWindowHandle != 0)
        $detected = $this->waitForWindow($safeApp, 5000);
        if (!$detected['success']) {
            $procCheck = trim($this->runPowerShell("Get-Process -Name '{$safeApp}' -ErrorAction SilentlyContinue | Where-Object { \$_.MainWindowHandle -ne 0 } | Select-Object -First 1 -ExpandProperty Id"));
            if (!empty($procCheck)) {
                $this->focusWindow($safeApp);
                return $this->logResult([
                    'success' => true,
                    'action' => 'open_application',
                    'app' => $safeApp,
                    'pid' => (int)$procCheck,
                    'message' => "Successfully launched {$safeApp} (PID {$procCheck})",
                ]);
            }

            return $this->logResult([
                'success' => false,
                'action' => 'open_application',
                'app' => $safeApp,
                'error' => "Application launched but visible window was not detected within timeout: " . ($detected['error'] ?? 'timeout'),
            ]);
        }

        $this->focusWindow($safeApp);
        return $this->logResult([
            'success' => true,
            'action' => 'open_application',
            'app' => $safeApp,
            'process_info' => $detected['window'] ?? [],
            'message' => "Successfully opened {$safeApp}",
        ]);
    }

    /**
     * Open a URL in Chrome or default web browser cleanly using the Windows Shell.
     */
    public function openUrl(string $url, ?string $browser = 'chrome'): array
    {
        $cleanUrl = trim($url);
        if (!filter_var($cleanUrl, FILTER_VALIDATE_URL) && !str_starts_with($cleanUrl, 'http://') && !str_starts_with($cleanUrl, 'https://')) {
            $cleanUrl = 'https://' . $cleanUrl;
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'open_url',
                'url' => $cleanUrl,
                'browser' => $browser ?? 'default',
                'simulated' => true,
            ]);
        }

        // Use native Windows ShellExecute (via start "") which attaches directly to the active user desktop
        // and never creates zombie/orphaned background helper processes.
        $escapedUrl = str_replace(['&', '|', '<', '>', '^'], ['^&', '^|', '^<', '^>', '^^'], $cleanUrl);
        if ($browser === 'chrome' || $browser === 'google-chrome') {
            pclose(popen("start \"\" chrome \"{$escapedUrl}\"", "r"));
            $this->focusWindow('chrome');
        } else {
            pclose(popen("start \"\" \"{$escapedUrl}\"", "r"));
        }

        return $this->logResult([
            'success' => true,
            'action' => 'open_url',
            'url' => $cleanUrl,
            'browser' => $browser ?? 'default',
            'message' => "Successfully opened URL in browser: {$cleanUrl}",
        ]);
    }

    /**
     * Search YouTube in Chrome and trigger video search or playback.
     */
    public function searchAndPlayYoutube(string $query, bool $directPlay = false): array
    {
        $cleanQuery = trim($query);
        $encoded = urlencode($cleanQuery);
        $url = "https://www.youtube.com/results?search_query={$encoded}";

        $browserRes = $this->openUrl($url, 'chrome');

        return $this->logResult([
            'success' => $browserRes['success'] ?? true,
            'action' => 'search_and_play_youtube',
            'query' => $cleanQuery,
            'url' => $url,
            'direct_play' => $directPlay,
            'message' => "Opened YouTube in Chrome for query: \"{$cleanQuery}\"",
        ]);
    }

    /**
     * Deterministically poll for application window without arbitrary long sleeps.
     */
    public function waitForWindow(string $titleOrProcess, int $timeoutMs = 5000): array
    {
        $safeQuery = trim(preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', $titleOrProcess));
        if ($safeQuery === '') {
            return [
                'success' => false,
                'action' => 'wait_for_window',
                'error' => 'Empty window title/process name.',
            ];
        }

        if ($this->platform !== 'Windows') {
            return [
                'success' => true,
                'action' => 'wait_for_window',
                'simulated' => true,
                'window' => ['process_name' => $safeQuery, 'title' => $safeQuery, 'pid' => 1234],
            ];
        }

        $startTime = microtime(true);
        $timeoutSec = $timeoutMs / 1000.0;

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
\$proc = Get-Process -Name "{$safeQuery}" -ErrorAction SilentlyContinue | Where-Object { \$_.MainWindowHandle -ne 0 } | Select-Object -First 1
if (\$proc) {
    [PSCustomObject]@{
        ProcessName = \$proc.ProcessName
        Id = \$proc.Id
        MainWindowTitle = \$proc.MainWindowTitle
        HasWindow = \$true
    } | ConvertTo-Json -Compress
} else {
    Write-Output "NOT_FOUND"
}
POWERSHELL;

        while ((microtime(true) - $startTime) < $timeoutSec) {
            $rawJson = trim($this->runPowerShell($psScript));

            if (!empty($rawJson) && $rawJson !== 'NOT_FOUND') {
                $data = json_decode($rawJson, true);
                if ($data && !empty($data['HasWindow'])) {
                    return [
                        'success' => true,
                        'action' => 'wait_for_window',
                        'window' => [
                            'process_name' => $data['ProcessName'] ?? $safeQuery,
                            'pid' => $data['Id'] ?? null,
                            'title' => $data['MainWindowTitle'] ?? '',
                        ],
                        'elapsed_ms' => round((microtime(true) - $startTime) * 1000, 1),
                    ];
                }
            }

            usleep(120000); // 120ms poll interval
        }

        return [
            'success' => false,
            'action' => 'wait_for_window',
            'error' => "Target window '{$safeQuery}' was not detected within {$timeoutMs}ms",
        ];
    }

    /**
     * Focus and bring target window to the foreground.
     */
    public function focusWindow(string $titleOrProcess): array
    {
        $safeQuery = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', $titleOrProcess);
        if (empty($safeQuery)) {
            return $this->logResult([
                'success' => false,
                'action' => 'focus_window',
                'error' => 'Invalid window title/process name.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'focus_window',
                'target' => $safeQuery,
                'simulated' => true,
            ]);
        }

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;
public class WinForeground {
    [DllImport("user32.dll")]
    public static extern bool SetForegroundWindow(IntPtr hWnd);
    [DllImport("user32.dll")]
    public static extern bool ShowWindowAsync(IntPtr hWnd, int nCmdShow);
    [DllImport("user32.dll")]
    public static extern void SwitchToThisWindow(IntPtr hWnd, bool fAltTab);
    [DllImport("user32.dll")]
    public static extern bool SetWindowPos(IntPtr hWnd, IntPtr hWndInsertAfter, int X, int Y, int cx, int cy, uint uFlags);
}
"@ -ErrorAction SilentlyContinue

\$focused = \$false
\$procs = Get-Process -Name "{$safeQuery}" -ErrorAction SilentlyContinue | Where-Object { \$_.MainWindowHandle -ne 0 }
if (\$procs) {
    foreach (\$p in \$procs) {
        [WinForeground]::ShowWindowAsync(\$p.MainWindowHandle, 9) | Out-Null
        [WinForeground]::ShowWindowAsync(\$p.MainWindowHandle, 3) | Out-Null
        [WinForeground]::SetWindowPos(\$p.MainWindowHandle, [IntPtr](-1), 0, 0, 0, 0, 0x0001 -bor 0x0002) | Out-Null
        [WinForeground]::SetWindowPos(\$p.MainWindowHandle, [IntPtr](-2), 0, 0, 0, 0, 0x0001 -bor 0x0002) | Out-Null
        [WinForeground]::SetForegroundWindow(\$p.MainWindowHandle) | Out-Null
        [WinForeground]::SwitchToThisWindow(\$p.MainWindowHandle, \$true)
        \$focused = \$true
        break
    }
}

try {
    \$wscript = New-Object -ComObject WScript.Shell
    for (\$i = 0; \$i -lt 5; \$i++) {
        if (\$wscript.AppActivate("{$safeQuery}") -or \$wscript.AppActivate("Untitled - {$safeQuery}") -or \$wscript.AppActivate("Notepad")) {
            \$focused = \$true
            break
        }
        Start-Sleep -Milliseconds 150
    }
} catch {}

if (\$focused) {
    Write-Output "FOCUSED"
} else {
    Write-Output "FAILED"
}
POWERSHELL;

        $res = trim($this->runPowerShell($psScript));

        if (str_contains($res, 'FOCUSED')) {
            usleep(100000); // 100ms settling time for Windows focus message pump
            return $this->logResult([
                'success' => true,
                'action' => 'focus_window',
                'target' => $safeQuery,
                'focused' => true,
            ]);
        }

        return $this->logResult([
            'success' => false,
            'action' => 'focus_window',
            'target' => $safeQuery,
            'error' => "Target window '{$safeQuery}' was not found or could not be focused.",
        ]);
    }

    /**
     * Type text into the currently focused or target window.
     */
    public function typeText(string $text, string $targetWindow = 'notepad'): array
    {
        $clean = trim($text);
        if ($clean === '') {
            return $this->logResult([
                'success' => false,
                'action' => 'type_text',
                'text' => $text,
                'error' => 'Input text cannot be empty.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'type_text',
                'text' => $text,
                'simulated' => true,
            ]);
        }

        // Ensure target window is focused first
        $focusRes = $this->focusWindow($targetWindow);
        if (!$focusRes['success']) {
            return $this->logResult([
                'success' => false,
                'action' => 'type_text',
                'text' => $text,
                'error' => "Target window was not found or could not be focused: " . ($focusRes['error'] ?? 'unknown'),
            ]);
        }

        // Escape text safely for SendKeys: { } + ^ % ~ ( ) [ ]
        $escapedText = $this->escapeSendKeys($text);

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
\$wscript = New-Object -ComObject WScript.Shell
\$typed = \$false
for (\$i = 0; \$i -lt 8; \$i++) {
    if (\$wscript.AppActivate("{$targetWindow}") -or \$wscript.AppActivate("Untitled - {$targetWindow}") -or \$wscript.AppActivate("Notepad")) {
        Start-Sleep -Milliseconds 150
        \$wscript.SendKeys("{$escapedText}")
        \$typed = \$true
        break
    }
    Start-Sleep -Milliseconds 150
}
if (\$typed) {
    Write-Output "TYPED"
} else {
    Write-Output "FOCUS_LOST"
}
POWERSHELL;

        $res = trim($this->runPowerShell($psScript));

        if (str_contains($res, 'TYPED')) {
            return $this->logResult([
                'success' => true,
                'action' => 'type_text',
                'text' => $text,
                'target' => $targetWindow,
            ]);
        }

        return $this->logResult([
            'success' => false,
            'action' => 'type_text',
            'text' => $text,
            'error' => "Failed to send keystrokes to '{$targetWindow}' (Status: {$res})",
        ]);
    }

    /**
     * Send special key combination (e.g. {ENTER}, {TAB}, ^s).
     */
    public function pressKey(string $key, string $targetWindow = 'notepad'): array
    {
        if (empty($key)) {
            return $this->logResult([
                'success' => false,
                'action' => 'press_key',
                'error' => 'Key cannot be empty.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'press_key',
                'key' => $key,
                'simulated' => true,
            ]);
        }

        $this->focusWindow($targetWindow);

        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
\$wscript = New-Object -ComObject WScript.Shell
\$wscript.SendKeys("{$key}")
Write-Output "KEY_SENT"
POWERSHELL;

        $this->runPowerShell($psScript);

        return $this->logResult([
            'success' => true,
            'action' => 'press_key',
            'key' => $key,
            'target' => $targetWindow,
        ]);
    }

    /**
     * Close a running desktop application by process name.
     */
    public function closeApplication(string $processName = 'notepad', bool $force = false): array
    {
        $safeProc = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $processName);
        if (empty($safeProc)) {
            return $this->logResult([
                'success' => false,
                'action' => 'close_application',
                'error' => 'Invalid process name.',
            ]);
        }

        if ($this->platform !== 'Windows') {
            return $this->logResult([
                'success' => true,
                'action' => 'close_application',
                'process' => $safeProc,
                'simulated' => true,
            ]);
        }

        $forceFlag = $force ? ' -Force' : '';
        $psScript = <<<POWERSHELL
\$ProgressPreference = 'SilentlyContinue'
Stop-Process -Name '{$safeProc}'{$forceFlag} -ErrorAction SilentlyContinue
POWERSHELL;

        $this->runPowerShell($psScript);

        return $this->logResult([
            'success' => true,
            'action' => 'close_application',
            'process' => $safeProc,
            'force' => $force,
            'message' => "Application '{$safeProc}' closed.",
        ]);
    }

    /**
     * Executes the exact Milestone 1 specification.
     *
     * START → Launch Notepad → Wait for window → Focus window → Type "Hi Vichu" → SUCCESS
     */
    public function runMilestoneOne(string $text = "Hi Vichu", ?callable $logger = null): array
    {
        $log = function(string $msg) use ($logger) {
            if ($logger !== null) {
                $logger($msg);
            }
        };

        $log("[ARC TWO] Starting...");
        $log("[ARC TWO] Opening Notepad...");

        $openRes = $this->openApplication('notepad');
        if (!$openRes['success']) {
            $log("[ARC TWO] FAILED: " . ($openRes['error'] ?? 'Could not open Notepad'));
            return [
                'success' => false,
                'step' => 'open_application',
                'error' => $openRes['error'],
            ];
        }

        $log("[ARC TWO] Notepad detected.");
        $log("[ARC TWO] Focusing window...");

        $focusRes = $this->focusWindow('notepad');
        if (!$focusRes['success']) {
            $log("[ARC TWO] FAILED: " . ($focusRes['error'] ?? 'Could not focus Notepad window'));
            return [
                'success' => false,
                'step' => 'focus_window',
                'error' => $focusRes['error'],
            ];
        }

        $log("[ARC TWO] Typing: {$text}");
        $typeRes = $this->typeText($text, 'notepad');
        if (!$typeRes['success']) {
            $log("[ARC TWO] FAILED: " . ($typeRes['error'] ?? 'Could not type text'));
            return [
                'success' => false,
                'step' => 'type_text',
                'error' => $typeRes['error'],
            ];
        }

        $log("[ARC TWO] SUCCESS");

        return [
            'success' => true,
            'action' => 'milestone_1',
            'app' => 'notepad',
            'typed_text' => $text,
            'message' => 'Milestone 1 completed successfully.',
        ];
    }

    /**
     * Execute a PowerShell script safely via Base64 UTF-16LE EncodedCommand.
     */
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

        // Strip any remaining CLIXML metadata
        $clean = preg_replace('/#<\s*CLIXML[\s\S]*?<\/Objs>/', '', $stdout);
        return trim($clean);
    }

    /**
     * Escape special characters for SendKeys simulation.
     */
    private function escapeSendKeys(string $text): string
    {
        // Special SendKeys characters: + ^ % ~ ( ) [ ] { }
        $search = ['{', '}', '+', '^', '%', '~', '(', ')', '[', ']'];
        $replace = ['{{}', '{}}', '{+}', '{^}', '{%}', '{~}', '{(}', '{)}', '{[}', '{]}'];
        return str_replace($search, $replace, $text);
    }

    private function logResult(array $result): array
    {
        $result['timestamp'] = date('c');
        $this->actionLog[] = $result;
        return $result;
    }
}

