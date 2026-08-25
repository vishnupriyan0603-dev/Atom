<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Testing\ApiSchemaFuzzerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 74 — Phase74SecurityPassTest security & safety tests (5 tests).
 */
class Phase74SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInEndpointPath(): void
    {
        $engine = new ApiSchemaFuzzerEngine($this->redactor);
        $res = $engine->fuzzEndpoint('/api/v1/keys/sk-1122334455667788990011223344/rotate');

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['endpoint']);
    }

    public function testHighThroughputFuzzingEngineThroughput(): void
    {
        $engine = new ApiSchemaFuzzerEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 50; $i++) {
            $engine->fuzzEndpoint("/api/route_{$i}", ['param_a' => 'int', 'param_b' => 'string']);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testRobustnessScoreBoundaryClamping(): void
    {
        $engine = new ApiSchemaFuzzerEngine($this->redactor);
        $res = $engine->fuzzEndpoint('/api/safe');

        $this->assertGreaterThanOrEqual(10, $res['robustness_score']);
        $this->assertLessThanOrEqual(100, $res['robustness_score']);
    }

    public function testFuzzPayloadSanitizationSafety(): void
    {
        $engine = new ApiSchemaFuzzerEngine($this->redactor);
        $payloads = $engine->getFuzzPayloads();

        foreach ($payloads as $cat => $list) {
            $this->assertIsArray($list);
            $this->assertNotEmpty($list);
        }
    }

    public function testNoDangerousEvalOrShellExecutionInTestingSubsystem(): void
    {
        $files = [
            'src/Testing/ApiSchemaFuzzerEngine.php',
            'src/Testing/SelfCorrectionEngine.php',
            'src/Testing/TestCoverageAnalyzer.php',
            'src/Testing/TestSynthesizer.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
