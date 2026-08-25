<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\AstPerformanceProfilerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 51 — AstPerformanceProfilerEngine unit tests (6 tests).
 */
class AstPerformanceProfilerEngineTest extends TestCase
{
    private AstPerformanceProfilerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AstPerformanceProfilerEngine(new SecretRedactor());
    }

    public function testDetectNestedLoopBottleneckComplexity(): void
    {
        $code = "foreach (\$a as \$x) { foreach (\$b as \$y) { if (\$x === \$y) \$m[] = \$x; } }";
        $analysis = $this->engine->analyze($code);

        $this->assertTrue($analysis['success']);
        $this->assertSame('O(N^2)', $analysis['complexity']);
        $this->assertGreaterThan(0, $analysis['bottlenecks_count']);
        $this->assertLessThan(100.0, $analysis['performance_score']);
    }

    public function testDetectNPlusOneQueryBottleneck(): void
    {
        $code = "foreach (\$ids as \$id) { \$user = \$db->query('SELECT * FROM users WHERE id = ' . \$id); }";
        $analysis = $this->engine->analyze($code);

        $this->assertTrue($analysis['success']);
        $this->assertSame('O(N * DB_RTT)', $analysis['complexity']);
        $this->assertStringContainsString('N_PLUS_ONE_QUERY', $analysis['bottlenecks'][0]['type']);
    }

    public function testDetectUnclosedStreamMemoryLeak(): void
    {
        $code = "\$fh = fopen('data.log', 'r'); \$line = fgets(\$fh);";
        $analysis = $this->engine->analyze($code);

        $this->assertTrue($analysis['success']);
        $this->assertGreaterThan(0, $analysis['memory_leaks_count']);
        $this->assertSame('UNCLOSED_FILE_HANDLE', $analysis['memory_leaks'][0]['type']);
    }

    public function testClosedStreamHasZeroLeaks(): void
    {
        $code = "\$fh = fopen('data.log', 'r'); \$line = fgets(\$fh); fclose(\$fh);";
        $analysis = $this->engine->analyze($code);

        $this->assertTrue($analysis['success']);
        $this->assertSame(0, $analysis['memory_leaks_count']);
    }

    public function testOptimizeNestedLoopToHashMap(): void
    {
        $code = "foreach (\$orders as \$order) { foreach (\$transactions as \$txn) { if (\$order['id'] === \$txn['id']) { \$matches[] = \$order; } } }";
        $opt = $this->engine->optimize($code);

        $this->assertTrue($opt['success']);
        $this->assertSame('O(N)', $opt['optimized_complexity']);
        $this->assertStringContainsString('array_column', $opt['optimized_code']);
        $this->assertStringContainsString('isset($map[', $opt['optimized_code']);
    }

    public function testEmptyInputFailsGracefully(): void
    {
        $res = $this->engine->analyze("   ");
        $this->assertFalse($res['success']);
        $this->assertSame('O(1)', $res['complexity']);
    }
}
