<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\SqlQueryIndexOptimizerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 52 — SqlQueryIndexOptimizerEngine unit tests (6 tests).
 */
class SqlQueryIndexOptimizerEngineTest extends TestCase
{
    private SqlQueryIndexOptimizerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SqlQueryIndexOptimizerEngine(new SecretRedactor());
    }

    public function testAnalyzeEqualityAndRangeQuery(): void
    {
        $sql = "SELECT * FROM orders WHERE user_id = 10 AND status = 'PAID' AND amount >= 50 ORDER BY created_at DESC;";
        $result = $this->engine->analyze($sql);

        $this->assertTrue($result['success']);
        $this->assertSame('orders', $result['table']);
        $this->assertContains('user_id', $result['recommended_index']['columns']);
        $this->assertContains('status', $result['recommended_index']['columns']);
        $this->assertContains('created_at', $result['recommended_index']['columns']);
        $this->assertGreaterThan(80.0, $result['cost_reduction_pct']);
    }

    public function testEsrRuleColumnOrdering(): void
    {
        $sql = "SELECT * FROM users WHERE status = 'ACTIVE' AND age > 21 ORDER BY last_login DESC;";
        $result = $this->engine->analyze($sql);

        $cols = $result['recommended_index']['columns'];
        // Equality (status) must precede sort (last_login) and range (age)
        $this->assertSame('status', $cols[0]);
    }

    public function testGenerateSqlDdlMigration(): void
    {
        $sql = "SELECT id FROM audit_logs WHERE tenant_id = 't1' AND severity = 'CRITICAL';";
        $result = $this->engine->analyze($sql);

        $this->assertStringStartsWith('CREATE INDEX idx_audit_logs_', $result['sql_ddl_migration']);
        $this->assertStringContainsString('ON audit_logs', $result['sql_ddl_migration']);
    }

    public function testGenerateCi4PhpMigrationClass(): void
    {
        $sql = "SELECT * FROM payments WHERE customer_id = 99;";
        $result = $this->engine->analyze($sql);

        $this->assertStringContainsString('class AddPaymentsPerformanceIndex extends Migration', $result['ci4_php_migration']);
        $this->assertStringContainsString('public function up()', $result['ci4_php_migration']);
        $this->assertStringContainsString('public function down()', $result['ci4_php_migration']);
    }

    public function testEmptySqlInputFailsGracefully(): void
    {
        $result = $this->engine->analyze("   ");
        $this->assertFalse($result['success']);
        $this->assertSame('unknown', $result['table']);
    }

    public function testCostEstimationLogic(): void
    {
        $sql = "SELECT * FROM products WHERE category_id = 5;";
        $result = $this->engine->analyze($sql);

        $this->assertGreaterThan($result['estimated_cost_after'], $result['estimated_cost_before']);
        $this->assertGreaterThan(0.0, $result['cost_reduction_pct']);
    }
}
