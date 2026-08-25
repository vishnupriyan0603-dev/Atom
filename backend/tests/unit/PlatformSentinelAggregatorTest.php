<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Orchestration\PlatformSentinelAggregator;
use Atom\Orchestration\UnifiedPlatformGatewayCrossbar;
use Atom\Security\SecretRedactor;

/**
 * Phase 50 — PlatformSentinelAggregator unit tests (6 tests).
 */
class PlatformSentinelAggregatorTest extends TestCase
{
    private PlatformSentinelAggregator $sentinel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sentinel = new PlatformSentinelAggregator(new UnifiedPlatformGatewayCrossbar(), new SecretRedactor());
    }

    public function testRunDiagnosticsAllPillarsPass(): void
    {
        $diag = $this->sentinel->runDiagnostics();

        $this->assertTrue($diag['success']);
        $this->assertSame(100.0, $diag['diagnostic_score']);
        $this->assertSame($diag['total_checks'], $diag['passed_checks']);
        $this->assertNotEmpty($diag['checks']);
    }

    public function testDiagnosticsIncludesMemoryHeadroom(): void
    {
        $diag = $this->sentinel->runDiagnostics();
        $checks = array_column($diag['checks'], 'check');

        $this->assertContains('Memory Headroom', $checks);
    }

    public function testDiagnosticsIncludesPqcEntropyAndBounds(): void
    {
        $diag = $this->sentinel->runDiagnostics();
        $checks = array_column($diag['checks'], 'check');

        $this->assertContains('PQC Cryptographic Entropy & Lattice Bounds', $checks);
    }

    public function testDiagnosticsIncludesVoiceFormantStability(): void
    {
        $diag = $this->sentinel->runDiagnostics();
        $checks = array_column($diag['checks'], 'check');

        $this->assertContains('Acoustic Voice Formant Filter Stability', $checks);
    }

    public function testAutonomousSelfHealingRoutine(): void
    {
        $heal = $this->sentinel->healPlatform();

        $this->assertTrue($heal['success']);
        $this->assertSame('PLATFORM_OPTIMIZED', $heal['status']);
        $this->assertNotEmpty($heal['actions_performed']);
        $this->assertGreaterThan(0, $heal['timestamp']);
    }

    public function testDiagnosticLatencyIsSubMillisecondOrFast(): void
    {
        $diag = $this->sentinel->runDiagnostics();
        $this->assertLessThan(500.0, $diag['duration_ms']);
    }
}
