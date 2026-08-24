<?php

use PHPUnit\Framework\TestCase;
use Atom\Daemon\ProactiveDaemon;

/**
 * Phase 25 — ProactiveDaemon unit tests (5 tests).
 */
class ProactiveDaemonTest extends TestCase
{
    private ProactiveDaemon $daemon;

    protected function setUp(): void
    {
        $this->daemon = new ProactiveDaemon();
    }

    public function testDaemonInitialStatusIsRunning(): void
    {
        $status = $this->daemon->getStatus();
        $this->assertSame('running', $status['state']);
        $this->assertSame(0, $status['pulses_executed']);
        $this->assertArrayHasKey('uptime_seconds', $status);
        $this->assertArrayHasKey('memory_mb', $status);
    }

    public function testPulseIncrementsPulseCount(): void
    {
        $pulse1 = $this->daemon->pulse();
        $this->assertSame(1, $pulse1['pulse_id']);
        $this->assertArrayHasKey('health', $pulse1);
        $this->assertArrayHasKey('healing', $pulse1);

        $pulse2 = $this->daemon->pulse();
        $this->assertSame(2, $pulse2['pulse_id']);
        $this->assertSame(2, $this->daemon->getStatus()['pulses_executed']);
    }

    public function testPulseReturnsHealthAndHealingPayloads(): void
    {
        $pulse = $this->daemon->pulse();
        $this->assertArrayHasKey('health_score', $pulse['health']);
        $this->assertArrayHasKey('actions_count', $pulse['healing']);
        $this->assertSame('completed', $pulse['healing']['status']);
    }

    public function testDaemonAccessorsReturnEngines(): void
    {
        $this->assertInstanceOf(\Atom\Daemon\BriefingEngine::class, $this->daemon->getBriefingEngine());
        $this->assertInstanceOf(\Atom\Daemon\WorkspaceHealthMonitor::class, $this->daemon->getHealthMonitor());
        $this->assertInstanceOf(\Atom\Daemon\AutoHealingEngine::class, $this->daemon->getHealingEngine());
    }

    public function testDaemonVersionString(): void
    {
        $status = $this->daemon->getStatus();
        $this->assertStringContainsKeyOrVal('phase25', $status['daemon_version']);
    }

    private function assertStringContainsKeyOrVal(string $needle, string $haystack): void
    {
        $this->assertTrue(str_contains($haystack, $needle));
    }
}
