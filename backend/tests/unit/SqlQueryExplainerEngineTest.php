<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\SqlQueryExplainerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 72 — SqlQueryExplainerEngine unit tests (6 tests).
 */
class SqlQueryExplainerEngineTest extends TestCase
{
    private SqlQueryExplainerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SqlQueryExplainerEngine(new SecretRedactor());
    }

    public function testExplainOptimalIndexedQuery(): void
    {
        $sql = "SELECT id, email FROM users WHERE tenant_id = 'tenant_1' AND is_active = 1;";
        $exp = $this->engine->explainQuery($sql);

        $this->assertTrue($exp['success']);
        $this->assertSame('users', $exp['table']);
        $this->assertSame('range', $exp['access_type']);
        $this->assertGreaterThanOrEqual(80, $exp['efficiency_score']);
        $this->assertCount(1, $exp['suggested_indexes']);
    }

    public function testDetectFullTableScanAntiPattern(): void
    {
        $sql = "SELECT * FROM audit_logs ORDER BY id DESC;";
        $exp = $this->engine->explainQuery($sql);

        $this->assertTrue($exp['success']);
        $this->assertSame('ALL', $exp['access_type']);
        $this->assertLessThan(60, $exp['efficiency_score']);
        $this->assertNotEmpty($exp['warnings']);
    }

    public function testSynthesizeCompositeIndexDdl(): void
    {
        $suggestions = $this->engine->synthesizeIndexSuggestions('orders', ['user_id', 'created_at']);

        $this->assertCount(1, $suggestions);
        $this->assertSame('idx_orders_user_id_created_at', $suggestions[0]['index_name']);
        $this->assertStringContainsString('CREATE INDEX idx_orders_user_id_created_at ON orders (user_id, created_at);', $suggestions[0]['sql_ddl']);
    }

    public function testEmptyQueryFailsGracefully(): void
    {
        $exp = $this->engine->explainQuery('');
        $this->assertFalse($exp['success']);
        $this->assertSame(0, $exp['efficiency_score']);
    }

    public function testWhereColumnsExtractionFiltersKeywords(): void
    {
        $sql = "SELECT * FROM accounts WHERE status = 'active' AND balance >= 100;";
        $exp = $this->engine->explainQuery($sql);

        $this->assertContains('status', $exp['filtered_columns']);
        $this->assertContains('balance', $exp['filtered_columns']);
        $this->assertNotContains('where', $exp['filtered_columns']);
    }

    public function testUpdateAndInsertQueryTableExtraction(): void
    {
        $updateExp = $this->engine->explainQuery("UPDATE customers SET name = 'Bob' WHERE id = 12;");
        $this->assertSame('customers', $updateExp['table']);

        $insertExp = $this->engine->explainQuery("INSERT INTO payments (id, amount) VALUES (1, 50);");
        $this->assertSame('payments', $insertExp['table']);
    }
}
