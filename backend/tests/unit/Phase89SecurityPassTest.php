<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Ai\ApiCostGovernorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 89 — Phase89SecurityPassTest security & safety tests (5 tests).
 */
class Phase89SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInTenantId(): void
    {
        $engine = new ApiCostGovernorEngine($this->redactor);
        $res = $engine->trackSpend('tenant_sk-1122334455667788990011223344_auth', 'gpt-4o', 100, 50);

        $this->assertTrue($res['allowed']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['tenant_id']);
    }

    public function testHighThroughputCostTracking(): void
    {
        $engine = new ApiCostGovernorEngine($this->redactor);
        $engine->setBudget('tenant_load_test', 10000.0);

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->trackSpend('tenant_load_test', 'gpt-4o', 500, 100);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testBudgetMinimumClampingSafety(): void
    {
        $engine = new ApiCostGovernorEngine($this->redactor);
        $engine->setBudget('tenant_micro', -100.0); // should clamp to 5.0

        $budgets = $engine->getAllBudgets();
        $map = array_column($budgets, null, 'tenant_id');
        $this->assertSame(5.0, $map['tenant_micro']['monthly_limit_usd']);
    }

    public function testThrottlingPreservesSpendLedger(): void
    {
        $engine = new ApiCostGovernorEngine($this->redactor);
        $engine->setBudget('tenant_fixed', 10.0);

        // Spend all $10
        $engine->trackSpend('tenant_fixed', 'gpt-4o', 4_000_000, 0); // 4M input = $10.00
        $res = $engine->trackSpend('tenant_fixed', 'gpt-4o', 100, 100);

        $this->assertFalse($res['allowed']);
        $this->assertSame(10.0, $res['current_spend_usd']);
    }

    public function testNoDangerousEvalOrShellExecutionInAiSubsystem(): void
    {
        $files = [
            'src/Ai/ApiCostGovernorEngine.php',
            'src/Ai/FederatedLearningEngine.php',
            'src/Brain/MetacognitiveReasoningEngine.php',
            'src/Brain/AtomBrain.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
