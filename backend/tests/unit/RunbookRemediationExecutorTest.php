<?php

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\RunbookRemediationExecutor;

/**
 * Phase 40 — RunbookRemediationExecutor unit tests (5 tests).
 */
class RunbookRemediationExecutorTest extends TestCase
{
    private RunbookRemediationExecutor $executor;

    protected function setUp(): void
    {
        $this->executor = new RunbookRemediationExecutor();
    }

    public function testExecuteRestartAndScaleWorkersRunbook(): void
    {
        $res = $this->executor->executeRunbook('restart_and_scale_workers', 'edge_workers');

        $this->assertSame('SUCCESS', $res['status']);
        $this->assertCount(3, $res['steps_taken']);
        $this->assertSame('edge_workers', $res['subsystem']);
    }

    public function testExecuteDrainConnectionPoolRunbook(): void
    {
        $res = $this->executor->executeRunbook('drain_connection_pool', 'database');

        $this->assertSame('SUCCESS', $res['status']);
        $this->assertStringContainsString('connection pool', $res['steps_taken'][0]);
    }

    public function testExecuteFlushCacheAndThrottleRunbook(): void
    {
        $res = $this->executor->executeRunbook('flush_cache_and_throttle', 'redis');

        $this->assertSame('SUCCESS', $res['status']);
        $this->assertStringContainsString('rate limit', $res['steps_taken'][1]);
    }

    public function testExecutionHistoryAppendsRuns(): void
    {
        $this->assertEmpty($this->executor->getHistory());

        $this->executor->executeRunbook('drain_connection_pool');
        $this->executor->executeRunbook('flush_cache_and_throttle');

        $this->assertCount(2, $this->executor->getHistory());
    }

    public function testFallbackDefaultRunbookExecutesSafely(): void
    {
        $res = $this->executor->executeRunbook('unknown_custom_playbook');

        $this->assertSame('SUCCESS', $res['status']);
        $this->assertNotEmpty($res['steps_taken']);
    }
}
