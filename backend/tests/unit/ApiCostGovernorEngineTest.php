<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Ai\ApiCostGovernorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 89 — ApiCostGovernorEngine unit tests (6 tests).
 */
class ApiCostGovernorEngineTest extends TestCase
{
    private ApiCostGovernorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new ApiCostGovernorEngine(new SecretRedactor());
    }

    public function testTrackSpendAccurateCostCalculation(): void
    {
        // 1M prompt ($2.50) + 1M completion ($10.00) = $12.50 on gpt-4o
        $this->engine->setBudget('tenant_test_1', 100.0);
        $res = $this->engine->trackSpend('tenant_test_1', 'gpt-4o', 1_000_000, 1_000_000);

        $this->assertTrue($res['allowed']);
        $this->assertSame(12.50, $res['call_cost_usd']);
        $this->assertSame(12.50, $res['current_spend_usd']);
        $this->assertSame(87.50, $res['budget_remaining_usd']);
    }

    public function testZeroCostSelfHostedModel(): void
    {
        $this->engine->setBudget('tenant_test_zero', 50.0);
        $res = $this->engine->trackSpend('tenant_test_zero', 'atom-neural-edge', 5_000_000, 5_000_000);

        $this->assertTrue($res['allowed']);
        $this->assertSame(0.0, $res['call_cost_usd']);
        $this->assertSame(0.0, $res['current_spend_usd']);
    }

    public function testExceedingHardBudgetThrottlesTenant(): void
    {
        $this->engine->setBudget('tenant_limited', 10.0);

        // Spend $12.50 on a $10 budget
        $res = $this->engine->trackSpend('tenant_limited', 'gpt-4o', 1_000_000, 1_000_000);

        $this->assertFalse($res['allowed']);
        $this->assertSame('THROTTLED_BUDGET_EXCEEDED', $res['status']);
        $this->assertStringContainsString('atom-neural-edge', $res['recommendation']);
    }

    public function testSoftWarningAtEightyPercent(): void
    {
        $this->engine->setBudget('tenant_warn', 10.0);

        // Spend $8.50 ($85%)
        $res = $this->engine->trackSpend('tenant_warn', 'gpt-4o', 1_000_000, 600_000); // 2.50 + 6.00 = 8.50

        $this->assertTrue($res['allowed']);
        $this->assertSame('WARNING_80_PCT', $res['status']);
    }

    public function testGetAllBudgetsReportsPercentages(): void
    {
        $budgets = $this->engine->getAllBudgets();

        $this->assertGreaterThanOrEqual(3, count($budgets));
        $this->assertArrayHasKey('spend_pct', $budgets[0]);
        $this->assertArrayHasKey('monthly_limit_usd', $budgets[0]);
    }

    public function testGetPricingMatrixContainsStandardModels(): void
    {
        $pricing = $this->engine->getPricingMatrix();

        $this->assertArrayHasKey('gpt-4o', $pricing);
        $this->assertArrayHasKey('claude-3-5-sonnet', $pricing);
        $this->assertArrayHasKey('gemini-1-5-pro', $pricing);
        $this->assertArrayHasKey('atom-neural-edge', $pricing);
    }
}
