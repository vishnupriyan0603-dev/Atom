<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Api\GraphQLComplexityAnalyzerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 83 — GraphQLComplexityAnalyzerEngine unit tests (6 tests).
 */
class GraphQLComplexityAnalyzerEngineTest extends TestCase
{
    private GraphQLComplexityAnalyzerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new GraphQLComplexityAnalyzerEngine(new SecretRedactor());
    }

    public function testAnalyzeValidLightweightQuery(): void
    {
        $query = 'query GetUser { user { id name email } }';
        $res = $this->engine->analyzeQuery($query);

        $this->assertTrue($res['success']);
        $this->assertTrue($res['allowed']);
        $this->assertSame(2, $res['max_depth']);
        $this->assertSame('QUERY_ALLOWED_WITHIN_BUDGET', $res['status']);
        $this->assertNull($res['rejection_reason']);
    }

    public function testRejectExcessiveDepthRecursiveQuery(): void
    {
        $query = 'query DeepNesting { a { b { c { d { e { f { g { h { i { j { k } } } } } } } } } } }';
        $res = $this->engine->analyzeQuery($query);

        $this->assertTrue($res['success']);
        $this->assertFalse($res['allowed']);
        $this->assertGreaterThan(7, $res['max_depth']);
        $this->assertSame('QUERY_BLOCKED', $res['status']);
        $this->assertStringContainsString('QUERY_DEPTH_EXCEEDED', $res['rejection_reason']);
    }

    public function testConnectionFieldsCarryHigherComplexityMultiplier(): void
    {
        $lightQuery = 'query ScalarOnly { user { id name email } }';
        $connectionQuery = 'query WithConnections { user { orders connection items nodes } }';

        $lightRes = $this->engine->analyzeQuery($lightQuery);
        $connRes = $this->engine->analyzeQuery($connectionQuery);

        $this->assertGreaterThan($lightRes['calculated_complexity'], $connRes['calculated_complexity']);
    }

    public function testEmptyQueryFailsGracefully(): void
    {
        $res = $this->engine->analyzeQuery('');
        $this->assertFalse($res['success']);
        $this->assertSame('REJECTED_EMPTY', $res['status']);
    }

    public function testBudgetLimitsInspection(): void
    {
        $limits = $this->engine->getBudgetLimits();

        $this->assertSame(7, $limits['max_depth']);
        $this->assertSame(250, $limits['max_complexity']);
        $this->assertSame(1, $limits['scalar_field_cost']);
        $this->assertSame(10, $limits['connection_field_cost']);
    }

    public function testComplexQueryOverBudgetBlocked(): void
    {
        // 30 connections with depth
        $fields = str_repeat("orders items nodes connection list ", 6);
        $query = "query HugePayload { root { {$fields} } }";

        $res = $this->engine->analyzeQuery($query);
        $this->assertTrue($res['success']);
        $this->assertFalse($res['allowed']);
        $this->assertStringContainsString('QUERY_COMPLEXITY_EXCEEDED', $res['rejection_reason']);
    }
}
