<?php

namespace Atom\Daemon;

/**
 * ProactiveDaemon — Master Background Assistant Daemon Core.
 *
 * Manages periodic pulses (heartbeats), scheduled life-cycle checks,
 * workspace health monitoring, and auto-healing remediation without
 * blocking user interactive threads.
 */
class ProactiveDaemon
{
    private WorkspaceHealthMonitor $healthMonitor;
    private AutoHealingEngine $healingEngine;
    private BriefingEngine $briefingEngine;

    private int $startTime;
    private int $pulseCount = 0;
    private array $lastPulseResult = [];
    private string $state = 'running';

    public function __construct(
        ?WorkspaceHealthMonitor $healthMonitor = null,
        ?AutoHealingEngine $healingEngine = null,
        ?BriefingEngine $briefingEngine = null
    ) {
        $this->healthMonitor = $healthMonitor ?? new WorkspaceHealthMonitor();
        $this->healingEngine = $healingEngine ?? new AutoHealingEngine();
        $this->briefingEngine = $briefingEngine ?? new BriefingEngine();
        $this->startTime = time();
    }

    /**
     * Trigger an autonomous daemon life-cycle pulse.
     */
    public function pulse(): array
    {
        $this->pulseCount++;
        $start = microtime(true);

        // 1. Scan workspace health
        $health = $this->healthMonitor->scanWorkspace();

        // 2. Run auto-healing if health degraded or routine tick
        $healing = $this->healingEngine->runHealingPass();

        // 3. Compute memory and duration
        $memoryMb = round(memory_get_usage(true) / (1024 * 1024), 2);
        $durationMs = (int) ((microtime(true) - $start) * 1000);

        $pulseData = [
            'pulse_id' => $this->pulseCount,
            'timestamp' => date('Y-m-d H:i:s'),
            'duration_ms' => $durationMs,
            'memory_mb' => $memoryMb,
            'uptime_seconds' => time() - $this->startTime,
            'health' => $health,
            'healing' => $healing,
            'status' => $health['status'],
        ];

        $this->lastPulseResult = $pulseData;
        return $pulseData;
    }

    /**
     * Get the live status and telemetry of the daemon.
     */
    public function getStatus(): array
    {
        return [
            'state' => $this->state,
            'uptime_seconds' => time() - $this->startTime,
            'pulses_executed' => $this->pulseCount,
            'memory_mb' => round(memory_get_usage(true) / (1024 * 1024), 2),
            'last_pulse' => $this->lastPulseResult,
            'daemon_version' => '1.0.0-phase25',
        ];
    }

    public function getBriefingEngine(): BriefingEngine
    {
        return $this->briefingEngine;
    }

    public function getHealthMonitor(): WorkspaceHealthMonitor
    {
        return $this->healthMonitor;
    }

    public function getHealingEngine(): AutoHealingEngine
    {
        return $this->healingEngine;
    }
}
