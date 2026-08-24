<?php

use PHPUnit\Framework\TestCase;
use Atom\Routing\CircuitBreaker;

class HealthMonitorAndCircuitBreakerTest extends TestCase
{
    public function testCircuitBreakerOpensOnRepeatedFailuresAndRecoversOnSuccess()
    {
        $cb = new CircuitBreaker();

        $this->assertTrue($cb->canRoute('groq'));
        $this->assertEquals('closed', $cb->getState('groq'));

        $cb->recordFailure('groq');
        $this->assertEquals('open', $cb->getState('groq'));
        $this->assertFalse($cb->canRoute('groq'));

        $cb->recordSuccess('groq');
        $this->assertEquals('closed', $cb->getState('groq'));
        $this->assertTrue($cb->canRoute('groq'));
    }
}
