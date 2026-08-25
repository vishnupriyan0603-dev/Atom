<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Telemetry\LatencyHeatmapEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 67 — Phase67SecurityPassTest security & safety tests (5 tests).
 */
class Phase67SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInSubsystemNames(): void
    {
        $engine = new LatencyHeatmapEngine($this->redactor);
        $res = $engine->recordLatency('Service_sk-1122334455667788990011223344', 12.0);

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['subsystem']);
    }

    public function testArithmeticStabilityNoNanOrInfinite(): void
    {
        $engine = new LatencyHeatmapEngine($this->redactor);
        $matrix = $engine->getHeatmapMatrix();

        $this->assertFalse(is_nan($matrix['sla_compliance_pct']));
        $this->assertFalse(is_infinite($matrix['sla_compliance_pct']));
    }

    public function testHighVolumeTelemetryThroughput(): void
    {
        $engine = new LatencyHeatmapEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->recordLatency("Subsystem_{$i}", (float) ($i % 50));
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testSlaThresholdBoundsClamped(): void
    {
        $engine = new LatencyHeatmapEngine($this->redactor);
        $res = $engine->recordLatency('BoundTest', 100000.0);

        $this->assertTrue($res['is_sla_breach']);
        $this->assertSame('p3_breach', $res['bucket']);
    }

    public function testNoDangerousEvalOrShellExecutionInTelemetrySubsystem(): void
    {
        $files = [
            'src/Telemetry/LatencyHeatmapEngine.php',
            'src/Telemetry/TelemetryManager.php',
            'src/Telemetry/Span.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
