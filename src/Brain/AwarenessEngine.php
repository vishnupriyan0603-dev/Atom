<?php

namespace Atom\Brain;

use Atom\Brain\Device\DeviceAbstraction;

/**
 * AwarenessEngine — local environment introspection for Atom.
 *
 * Provides Atom with awareness of:
 *  - Current time and day (IST — Asia/Kolkata)
 *  - Device/runtime context (cli, web, flutter, wpf)
 *  - PHP version and workspace file delta
 *  - Time-of-day greeting hint
 *
 * Does NOT call external APIs. All information is derived from
 * local PHP environment, filesystem, and the system clock.
 */
class AwarenessEngine
{
    private string $workspaceRoot;
    private DeviceAbstraction $device;

    /** Cached file count from the previous request for delta computation. */
    private int $previousFileCount = 0;

    public function __construct(string $workspaceRoot, ?DeviceAbstraction $device = null)
    {
        $this->workspaceRoot = rtrim($workspaceRoot, '/\\');
        $this->device        = $device ?? new DeviceAbstraction();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build the environment awareness block injected into the system prompt.
     */
    public function getEnvironmentBlock(): string
    {
        $env  = $this->collectEnvironment();
        $lines = ['--- AWARENESS (ENVIRONMENT) ---'];

        $lines[] = "Time (IST): " . $env['time_ist'];
        $lines[] = "Day: " . $env['day_of_week'];
        $lines[] = "Time of Day: " . $env['time_of_day'];
        $lines[] = "Device Context: " . $env['device'];
        $lines[] = "PHP Version: " . $env['php_version'];
        $lines[] = "Workspace File Count: " . $env['file_count'];
        if ($env['file_delta'] !== 0) {
            $sign = $env['file_delta'] > 0 ? '+' : '';
            $lines[] = "Workspace Delta Since Last Request: {$sign}{$env['file_delta']} files";
        }
        $lines[] = '--------------------------------';

        return implode("\n", $lines);
    }

    /**
     * Get all awareness data as a structured array.
     */
    public function getEnvironmentData(): array
    {
        return $this->collectEnvironment();
    }

    public function getDeviceContext(): DeviceAbstraction
    {
        return $this->device;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal
    // ──────────────────────────────────────────────────────────────────────────

    private function collectEnvironment(): array
    {
        $tz   = new \DateTimeZone('Asia/Kolkata');
        $now  = new \DateTimeImmutable('now', $tz);
        $hour = (int) $now->format('G');

        $timeOfDay = match (true) {
            $hour >= 5 && $hour < 12  => 'Morning',
            $hour >= 12 && $hour < 17 => 'Afternoon',
            $hour >= 17 && $hour < 21 => 'Evening',
            default                    => 'Night',
        };

        $fileCount       = $this->countWorkspaceFiles();
        $delta           = $fileCount - $this->previousFileCount;
        $this->previousFileCount = $fileCount;

        return [
            'time_ist'     => $now->format('D, d M Y H:i:s') . ' IST',
            'day_of_week'  => $now->format('l'),
            'time_of_day'  => $timeOfDay,
            'device'       => $this->device->getDeviceType(),
            'php_version'  => PHP_VERSION,
            'file_count'   => $fileCount,
            'file_delta'   => ($this->previousFileCount === $fileCount) ? 0 : $delta,
        ];
    }

    /**
     * Quick recursive file count — does NOT read file content.
     * Skips hidden directories (.git, vendor, node_modules).
     */
    private function countWorkspaceFiles(): int
    {
        if (!is_dir($this->workspaceRoot)) {
            return 0;
        }

        $skip = ['.git', 'vendor', 'node_modules', '.idea', '__pycache__'];
        $count = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator(
                        $this->workspaceRoot,
                        \RecursiveDirectoryIterator::SKIP_DOTS
                    ),
                    function (\SplFileInfo $file, $key, \RecursiveDirectoryIterator $it) use ($skip): bool {
                        if ($it->hasChildren() && in_array($file->getFilename(), $skip, true)) {
                            return false;
                        }
                        return true;
                    }
                ),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $count++;
                }
            }
        } catch (\Throwable $e) {
            // Filesystem error — return cached count
        }

        return $count;
    }
}
