<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\WasmSandboxRuntimeEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 101 — WasmSandboxRuntimeEngine unit tests (6 tests).
 */
class WasmSandboxRuntimeEngineTest extends TestCase
{
    private WasmSandboxRuntimeEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new WasmSandboxRuntimeEngine(new SecretRedactor());
    }

    public function testExecuteVectorDotProductComputesCorrectly(): void
    {
        $res = $this->engine->execute('vector_dot_product', [[1.0, 2.0, 3.0], [4.0, 5.0, 6.0]], 1000);

        $this->assertTrue($res['success']);
        $this->assertSame(32.0, $res['result']); // 1*4 + 2*5 + 3*6 = 32
        $this->assertSame('COMPLETED', $res['status']);
        $this->assertGreaterThan(0, $res['gas_consumed']);
    }

    public function testExecuteFastHashCrc32(): void
    {
        $res = $this->engine->execute('fast_hash_crc32', ['hello_wasm']);

        $this->assertTrue($res['success']);
        $this->assertSame(sprintf('%u', crc32('hello_wasm')), $res['result']);
    }

    public function testGasLimitExceededTrapsCleanly(): void
    {
        // Very low gas limit
        $res = $this->engine->execute('fast_hash_crc32', [str_repeat('A', 500)], 100);

        $this->assertFalse($res['success']);
        $this->assertSame('TRAPPED_ERROR', $res['status']);
        $this->assertSame('GAS_LIMIT_EXCEEDED', $res['result']);
    }

    public function testLinearMemoryTransformAppliesByteTransformation(): void
    {
        $asciiA = ord('A'); // 65
        $res = $this->engine->execute('linear_memory_transform', [[$asciiA]]);

        $this->assertTrue($res['success']);
        $this->assertSame([ord('a')], $res['result']); // 97 (case inverted)
    }

    public function testUnknownFunctionReturnsNoop(): void
    {
        $res = $this->engine->execute('unknown_custom_op');

        $this->assertTrue($res['success']);
        $this->assertSame('OK_NOOP', $res['result']);
    }

    public function testGetRuntimeProfileReturnsConfig(): void
    {
        $profile = $this->engine->getRuntimeProfile();

        $this->assertSame(64, $profile['max_memory_mb']);
        $this->assertContains('vector_dot_product', $profile['supported_functions']);
    }
}
