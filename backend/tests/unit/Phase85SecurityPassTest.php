<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\DynamicCircuitBreakerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 85 — Phase85SecurityPassTest security & safety tests (5 tests).
 */
class Phase85SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInServiceName(): void
    {
        $engine = new DynamicCircuitBreakerEngine($this->redactor);
        $engine->registerCircuit('svc_sk-1122334455667788990011223344_auth');

        $circuits = $engine->getAllCircuits();
        foreach ($circuits as $c) {
            $this->assertStringNotContainsString('sk-1122334455667788990011223344', $c['service_name']);
        }
    }

    public function testHighThroughputCircuitExecution(): void
    {
        $engine = new DynamicCircuitBreakerEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->execute('payment_gateway_api', fn() => $i * 2);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testFallbackValueIntegrityWhenTripped(): void
    {
        $engine = new DynamicCircuitBreakerEngine($this->redactor);
        $engine->setCircuitState('payment_gateway_api', 'OPEN');

        $fallbackPayload = ['status' => 'FALLBACK_CACHED_ITEM', 'count' => 10];
        $res = $engine->execute('payment_gateway_api', fn() => throw new \Exception('Unreachable'), $fallbackPayload);

        $this->assertFalse($res['success']);
        $this->assertSame($fallbackPayload, $res['result']);
        $this->assertSame('OPEN', $res['circuit_state']);
    }

    public function testErrorRateBoundedBetweenZeroAndHundred(): void
    {
        $engine = new DynamicCircuitBreakerEngine($this->redactor);
        $circuits = $engine->getAllCircuits();

        foreach ($circuits as $c) {
            $this->assertGreaterThanOrEqual(0.0, $c['error_rate_pct']);
            $this->assertLessThanOrEqual(100.0, $c['error_rate_pct']);
        }
    }

    public function testNoDangerousEvalOrShellExecutionInInfrastructureSubsystem(): void
    {
        $files = [
            'src/Infrastructure/DynamicCircuitBreakerEngine.php',
            'src/Infrastructure/ChaosEngineeringMeshEngine.php',
            'src/Infrastructure/CanaryTrafficSplitEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
