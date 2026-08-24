<?php

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\IncidentEventClassifier;

/**
 * Phase 40 — IncidentEventClassifier unit tests (5 tests).
 */
class IncidentEventClassifierTest extends TestCase
{
    private IncidentEventClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new IncidentEventClassifier();
    }

    public function testFatalOOMClassifiedAsSev1Critical(): void
    {
        $event = [
            'message'    => 'Fatal error: Out of memory in worker thread',
            'error_rate' => 60.0,
            'subsystem'  => 'runtime_engine',
        ];
        $res = $this->classifier->classify($event);

        $this->assertSame('SEV1_CRITICAL', $res['severity']);
        $this->assertSame('restart_and_scale_workers', $res['recommended_action']);
    }

    public function testDatabaseDeadlockClassifiedAsSev2Major(): void
    {
        $event = [
            'message'    => 'Database connection refused after deadlock',
            'error_rate' => 25.0,
            'subsystem'  => 'database_pool',
        ];
        $res = $this->classifier->classify($event);

        $this->assertSame('SEV2_MAJOR', $res['severity']);
        $this->assertSame('drain_connection_pool', $res['recommended_action']);
    }

    public function testTimeoutAnomalyClassifiedAsSev3Moderate(): void
    {
        $event = [
            'message'    => 'HTTP gateway timeout on edge proxy',
            'error_rate' => 8.0,
            'subsystem'  => 'api_gateway',
        ];
        $res = $this->classifier->classify($event);

        $this->assertSame('SEV3_MODERATE', $res['severity']);
        $this->assertSame('flush_cache_and_throttle', $res['recommended_action']);
    }

    public function testNominalWarningClassifiedAsSev4Low(): void
    {
        $event = [
            'message'    => 'Slow query completed in 250ms',
            'error_rate' => 0.0,
            'subsystem'  => 'query_runner',
        ];
        $res = $this->classifier->classify($event);

        $this->assertSame('SEV4_LOW', $res['severity']);
    }

    public function testIncidentPayloadContainsUniqueId(): void
    {
        $res = $this->classifier->classify(['message' => 'test']);

        $this->assertNotEmpty($res['incident_id']);
        $this->assertStringStartsWith('inc_', $res['incident_id']);
    }
}
