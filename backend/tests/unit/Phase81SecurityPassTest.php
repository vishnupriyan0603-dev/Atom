<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\ChaosEngineeringMeshEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 81 — Phase81SecurityPassTest security & safety tests (5 tests).
 */
class Phase81SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInExperimentIdAndRequest(): void
    {
        $engine = new ChaosEngineeringMeshEngine($this->redactor);
        $res = $engine->startExperiment('exp_sk-1122334455667788990011223344_test', 'latency');

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['experiment_id']);
    }

    public function testHighThroughputFaultEvaluation(): void
    {
        $engine = new ChaosEngineeringMeshEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->shouldInjectFault("req_{$i}", "/api/route_" . ($i % 5));
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testBlastRadiusStrictUpperCap(): void
    {
        $engine = new ChaosEngineeringMeshEngine($this->redactor);
        $res = $engine->startExperiment('exp_extreme_blast', 'latency', 1000);

        $this->assertLessThanOrEqual(25, $res['blast_radius_pct']);
        $this->assertGreaterThanOrEqual(1, $res['blast_radius_pct']);
    }

    public function testEmergencyStopEnforcesImmediateHalt(): void
    {
        $engine = new ChaosEngineeringMeshEngine($this->redactor);
        $engine->startExperiment('live_exp', 'latency', 25);
        $engine->stopExperiment(null);

        for ($i = 0; $i < 50; $i++) {
            $eval = $engine->shouldInjectFault("req_{$i}", '/api/users/profile');
            $this->assertFalse($eval['inject_fault']);
            $this->assertSame('NO_ACTIVE_CHAOS', $eval['reason']);
        }
    }

    public function testNoDangerousEvalOrShellExecutionInInfrastructureSubsystem(): void
    {
        $files = [
            'src/Infrastructure/ChaosEngineeringMeshEngine.php',
            'src/Infrastructure/CanaryTrafficSplitEngine.php',
            'src/Infrastructure/CircuitBreakerOrchestrator.php',
            'src/Infrastructure/IncidentEventClassifier.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
