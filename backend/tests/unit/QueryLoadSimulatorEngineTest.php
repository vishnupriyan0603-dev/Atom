<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\QueryLoadSimulatorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 61 — QueryLoadSimulatorEngine unit tests (6 tests).
 */
class QueryLoadSimulatorEngineTest extends TestCase
{
    private QueryLoadSimulatorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new QueryLoadSimulatorEngine(new SecretRedactor());
    }

    public function testSimulateLoadProducesPercentileMetrics(): void
    {
        $sql = 'SELECT * FROM users WHERE email = "test@atom.local"';
        $res = $this->engine->simulateLoad($sql, 100, true);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(0.0, $res['qps']);
        $this->assertGreaterThan(0.0, $res['p50_latency_ms']);
        $this->assertGreaterThanOrEqual($res['p50_latency_ms'], $res['p90_latency_ms']);
        $this->assertGreaterThanOrEqual($res['p90_latency_ms'], $res['p99_latency_ms']);
    }

    public function testCompareIndexingImpactDemonstratesSpeedup(): void
    {
        $sql = 'SELECT * FROM orders WHERE status = "PENDING"';
        $res = $this->engine->compareIndexingImpact($sql, 50);

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('FASTER', $res['speedup_multiplier']);
        $this->assertGreaterThan($res['after_indexed']['p99_latency_ms'] * 2, $res['before_unindexed']['p99_latency_ms']);
        $this->assertGreaterThan(0.0, $res['throughput_gain_pct']);
    }

    public function testEmptySqlFailsGracefully(): void
    {
        $res = $this->engine->simulateLoad('', 50);

        $this->assertFalse($res['success']);
        $this->assertSame(0.0, $res['qps']);
    }

    public function testIterationsAreClampedWithinSafeBounds(): void
    {
        $resLow = $this->engine->simulateLoad('SELECT 1', 2);
        $this->assertSame(10, $resLow['iterations']); // Clamped to min 10

        $resHigh = $this->engine->simulateLoad('SELECT 1', 5000);
        $this->assertSame(1000, $resHigh['iterations']); // Clamped to max 1000
    }

    public function testUnindexedStatusVsIndexedStatus(): void
    {
        $resUnindexed = $this->engine->simulateLoad('SELECT * FROM logs', 50, false);
        $this->assertSame('UNINDEXED_FULL_SCAN', $resUnindexed['status']);

        $resIndexed = $this->engine->simulateLoad('SELECT * FROM logs', 50, true);
        $this->assertSame('OPTIMIZED_SEEK', $resIndexed['status']);
    }

    public function testSqlSnippetTruncation(): void
    {
        $longSql = 'SELECT * FROM logs WHERE message = "' . str_repeat('X', 200) . '"';
        $res = $this->engine->simulateLoad($longSql, 50);

        $this->assertTrue($res['success']);
        $this->assertLessThanOrEqual(83, strlen($res['sql']));
    }
}
