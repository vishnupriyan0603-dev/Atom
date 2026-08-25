<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\DynamicCircuitBreakerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 85 — DynamicCircuitBreakerEngine unit tests (6 tests).
 */
class DynamicCircuitBreakerEngineTest extends TestCase
{
    private DynamicCircuitBreakerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DynamicCircuitBreakerEngine(new SecretRedactor());
    }

    public function testExecuteSuccessKeepsCircuitClosed(): void
    {
        $res = $this->engine->execute('payment_gateway_api', fn() => 'TRANSACTION_SUCCESS');

        $this->assertTrue($res['success']);
        $this->assertSame('CLOSED', $res['circuit_state']);
        $this->assertSame('TRANSACTION_SUCCESS', $res['result']);
        $this->assertSame('EXECUTION_SUCCESS', $res['reason']);
    }

    public function testConsecutiveFailuresTripCircuitToOpen(): void
    {
        $engine = new DynamicCircuitBreakerEngine(new SecretRedactor());
        $engine->registerCircuit('test_fragile_service', 50.0, 5.0);

        // Execute 5 failures to trigger evaluation (>50% of 5)
        for ($i = 0; $i < 5; $i++) {
            $engine->execute('test_fragile_service', function () {
                throw new \RuntimeException('Database unreachable');
            }, 'CUSTOM_FALLBACK');
        }

        // The 6th request should fail fast because circuit is now OPEN
        $res = $engine->execute('test_fragile_service', fn() => 'NEVER_REACHED', 'CUSTOM_FALLBACK');

        $this->assertFalse($res['success']);
        $this->assertSame('OPEN', $res['circuit_state']);
        $this->assertSame('CUSTOM_FALLBACK', $res['result']);
        $this->assertSame('CIRCUIT_OPEN_FAST_FAIL', $res['reason']);
    }

    public function testSetCircuitStateManualOverride(): void
    {
        $this->assertTrue($this->engine->setCircuitState('payment_gateway_api', 'OPEN'));

        $circuits = $this->engine->getAllCircuits();
        $map = array_column($circuits, null, 'service_name');
        $this->assertSame('OPEN', $map['payment_gateway_api']['state']);

        // Reset back to CLOSED
        $this->assertTrue($this->engine->setCircuitState('payment_gateway_api', 'CLOSED'));
        $circuits = $this->engine->getAllCircuits();
        $map = array_column($circuits, null, 'service_name');
        $this->assertSame('CLOSED', $map['payment_gateway_api']['state']);
    }

    public function testNonExistentServiceAutoRegistersOnExecute(): void
    {
        $res = $this->engine->execute('dynamic_new_service_xyz', fn() => 42);

        $this->assertTrue($res['success']);
        $this->assertSame('CLOSED', $res['circuit_state']);
    }

    public function testInvalidStateChangeRejected(): void
    {
        $this->assertFalse($this->engine->setCircuitState('payment_gateway_api', 'INVALID_STATE_XYZ'));
        $this->assertFalse($this->engine->setCircuitState('non_existent_service_123', 'CLOSED'));
    }

    public function testGetAllCircuitsReportsErrorRate(): void
    {
        $circuits = $this->engine->getAllCircuits();

        $this->assertGreaterThanOrEqual(3, count($circuits));
        $this->assertArrayHasKey('error_rate_pct', $circuits[0]);
        $this->assertArrayHasKey('time_in_state_s', $circuits[0]);
    }
}
