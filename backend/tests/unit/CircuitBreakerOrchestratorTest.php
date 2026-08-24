<?php

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\CircuitBreakerOrchestrator;

/**
 * Phase 40 — CircuitBreakerOrchestrator unit tests (5 tests).
 */
class CircuitBreakerOrchestratorTest extends TestCase
{
    private CircuitBreakerOrchestrator $cb;

    protected function setUp(): void
    {
        $this->cb = new CircuitBreakerOrchestrator('test_service', 3, 0.2); // 3 failures, 200ms cooldown
    }

    public function testInitialCircuitStateIsClosed(): void
    {
        $this->assertSame('CLOSED', $this->cb->getState());
        $this->assertTrue($this->cb->allowExecution());
        $this->assertSame(0, $this->cb->getFailureCount());
    }

    public function testFailuresBelowThresholdKeepCircuitClosed(): void
    {
        $this->cb->recordFailure();
        $this->cb->recordFailure();

        $this->assertSame('CLOSED', $this->cb->getState());
        $this->assertTrue($this->cb->allowExecution());
        $this->assertSame(2, $this->cb->getFailureCount());
    }

    public function testExceedingThresholdTripsCircuitToOpen(): void
    {
        $this->cb->recordFailure();
        $this->cb->recordFailure();
        $this->cb->recordFailure(); // 3rd failure trips

        $this->assertSame('OPEN', $this->cb->getState());
        $this->assertFalse($this->cb->allowExecution());
    }

    public function testSuccessResetsFailureCount(): void
    {
        $this->cb->recordFailure();
        $this->cb->recordFailure();
        $this->cb->recordSuccess();

        $this->assertSame(0, $this->cb->getFailureCount());
        $this->assertSame('CLOSED', $this->cb->getState());
    }

    public function testCooldownTransitionsOpenToHalfOpen(): void
    {
        // Trip circuit
        $this->cb->recordFailure();
        $this->cb->recordFailure();
        $this->cb->recordFailure();
        $this->assertSame('OPEN', $this->cb->getState());

        // Wait past 200ms cooldown
        usleep(250000);

        $this->assertSame('HALF_OPEN', $this->cb->getState());
        $this->assertTrue($this->cb->allowExecution());
    }
}
