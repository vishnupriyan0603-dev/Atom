<?php

use PHPUnit\Framework\TestCase;
use Atom\Daemon\WorkspaceHealthMonitor;

/**
 * Phase 25 — WorkspaceHealthMonitor unit tests (5 tests).
 */
class WorkspaceHealthMonitorTest extends TestCase
{
    private WorkspaceHealthMonitor $monitor;

    protected function setUp(): void
    {
        $this->monitor = new WorkspaceHealthMonitor();
    }

    public function testScanWorkspaceReturnsValidStructure(): void
    {
        $report = $this->monitor->scanWorkspace();
        $this->assertArrayHasKey('health_score', $report);
        $this->assertArrayHasKey('status', $report);
        $this->assertArrayHasKey('syntax', $report);
        $this->assertArrayHasKey('database', $report);
        $this->assertArrayHasKey('disk', $report);
        $this->assertArrayHasKey('git', $report);
    }

    public function testHealthScoreRange(): void
    {
        $report = $this->monitor->scanWorkspace();
        $this->assertGreaterThanOrEqual(0, $report['health_score']);
        $this->assertLessThanOrEqual(100, $report['health_score']);
    }

    public function testDatabaseHealthCheck(): void
    {
        $report = $this->monitor->scanWorkspace();
        $this->assertTrue($report['database']['connected']);
        $this->assertSame('operational', $report['database']['status']);
    }

    public function testDiskHeadroomCheck(): void
    {
        $report = $this->monitor->scanWorkspace();
        $this->assertGreaterThan(0, $report['disk']['total_mb']);
        $this->assertArrayHasKey('used_percent', $report['disk']);
    }

    public function testGitStatusDetection(): void
    {
        $report = $this->monitor->scanWorkspace();
        $this->assertNotEmpty($report['git']['active_branch']);
    }
}
