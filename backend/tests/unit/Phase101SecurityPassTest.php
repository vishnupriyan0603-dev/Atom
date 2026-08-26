<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\WasmSandboxRuntimeEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 101 — Phase101SecurityPassTest security & safety tests (5 tests).
 */
class Phase101SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInWasmFunctionName(): void
    {
        $engine = new WasmSandboxRuntimeEngine($this->redactor);
        $res = $engine->execute('func_sk-1122334455667788990011223344_op', []);

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['function']);
    }

    public function testHighThroughputWasmExecution(): void
    {
        $engine = new WasmSandboxRuntimeEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->execute('vector_dot_product', [[1, 2], [3, 4]], 1000);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testGasLimitClampingSafety(): void
    {
        $engine = new WasmSandboxRuntimeEngine($this->redactor);

        // Extreme gas limits
        $resMax = $engine->execute('fast_hash_crc32', ['test'], 99999999);
        $this->assertSame(100000, $resMax['gas_limit']);

        $resMin = $engine->execute('fast_hash_crc32', ['test'], -50);
        $this->assertSame(100, $resMin['gas_limit']);
    }

    public function testNonStandardArgumentsHandledWithoutPanic(): void
    {
        $engine = new WasmSandboxRuntimeEngine($this->redactor);
        $res = $engine->execute('vector_dot_product', ['invalid_non_array', null]);

        $this->assertTrue($res['success']);
        $this->assertSame(0.0, $res['result']);
    }

    public function testNoDangerousEvalOrShellExecutionInInfrastructureSubsystem(): void
    {
        $files = [
            'src/Infrastructure/WasmSandboxRuntimeEngine.php',
            'src/Infrastructure/FeatureFlagRolloutEngine.php',
            'src/Infrastructure/DynamicCircuitBreakerEngine.php',
            'src/Infrastructure/ChaosEngineeringMeshEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
