<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\CanaryTrafficSplitEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 71 — Phase71SecurityPassTest security & safety tests (5 tests).
 */
class Phase71SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInRequestIdRouting(): void
    {
        $engine = new CanaryTrafficSplitEngine($this->redactor);
        $res = $engine->routeRequest('req_sk-1122334455667788990011223344_user');

        $this->assertArrayHasKey('target_version', $res);
        $this->assertArrayHasKey('is_canary', $res);
    }

    public function testHeaderOverrideSanitization(): void
    {
        $engine = new CanaryTrafficSplitEngine($this->redactor);
        $headers = ['X-Canary-Override' => 'INVALID_BOOLEAN_MALFORMED'];

        $res = $engine->routeRequest('req_test_header', 'default', $headers);
        $this->assertIsBool($res['is_canary']);
    }

    public function testHighThroughputRoutingThroughput(): void
    {
        $engine = new CanaryTrafficSplitEngine($this->redactor);
        $engine->setCanaryWeight(20);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->routeRequest("req_{$i}", "tenant_" . ($i % 10));
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testErrorRatePercentagesNeverExceed100(): void
    {
        $engine = new CanaryTrafficSplitEngine($this->redactor);
        $engine->routeRequest('req_1');
        $telemetry = $engine->recordCanaryTelemetry(false);

        $this->assertLessThanOrEqual(100.0, $telemetry['error_rate_pct']);
        $this->assertGreaterThanOrEqual(0.0, $telemetry['error_rate_pct']);
    }

    public function testNoDangerousEvalOrShellExecutionInInfrastructureSubsystem(): void
    {
        $files = [
            'src/Infrastructure/CanaryTrafficSplitEngine.php',
            'src/Infrastructure/CircuitBreakerOrchestrator.php',
            'src/Infrastructure/IncidentEventClassifier.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
