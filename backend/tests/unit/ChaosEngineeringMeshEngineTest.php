<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\ChaosEngineeringMeshEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 81 — ChaosEngineeringMeshEngine unit tests (6 tests).
 */
class ChaosEngineeringMeshEngineTest extends TestCase
{
    private ChaosEngineeringMeshEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ChaosEngineeringMeshEngine(new SecretRedactor());
    }

    public function testStartValidChaosExperiment(): void
    {
        $res = $this->engine->startExperiment('test_exp_latency', 'latency', 15, ['/api/test']);

        $this->assertTrue($res['success']);
        $this->assertSame('latency', $res['fault_type']);
        $this->assertSame(15, $res['blast_radius_pct']);
        $this->assertSame('EXPERIMENT_ACTIVE', $res['status']);
    }

    public function testInvalidFaultTypeRejected(): void
    {
        $res = $this->engine->startExperiment('bad_exp', 'unsupported_nuclear_wipe');

        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Invalid fault type', $res['error']);
    }

    public function testBlastRadiusCappedAtMaxLimit(): void
    {
        $res = $this->engine->startExperiment('capped_exp', 'http_500_error', 90); // requested 90%

        $this->assertSame(25, $res['blast_radius_pct']); // capped to 25%
    }

    public function testEmergencyStopTerminatesAllExperiments(): void
    {
        $this->engine->startExperiment('exp_1', 'latency', 10);
        $this->engine->startExperiment('exp_2', 'http_500_error', 10);

        $this->engine->stopExperiment(null); // Emergency stop all

        $status = $this->engine->getActiveExperiments();
        $this->assertTrue($status['emergency_stop_engaged']);
        $this->assertSame(0, $status['active_count']);

        $eval = $this->engine->shouldInjectFault('req_123', '/api/test');
        $this->assertFalse($eval['inject_fault']);
    }

    public function testTargetEndpointFilteringInFaultInjection(): void
    {
        $this->engine->startExperiment('scoped_exp', 'latency', 25, ['/api/specific/route']);

        // Non-matching route should not have fault injected
        $nonMatching = $this->engine->shouldInjectFault('req_abc', '/api/unrelated/route');
        $this->assertFalse($nonMatching['inject_fault']);
    }

    public function testStopSpecificExperimentOnly(): void
    {
        $this->engine->startExperiment('exp_single_stop', 'latency', 10);
        $this->assertTrue($this->engine->stopExperiment('exp_single_stop'));
        $this->assertFalse($this->engine->stopExperiment('non_existent_exp_id'));
    }
}
