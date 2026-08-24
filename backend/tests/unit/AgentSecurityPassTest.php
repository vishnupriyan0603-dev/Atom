<?php

use PHPUnit\Framework\TestCase;
use Atom\Agent\RiskEngine;
use Atom\Agent\AgentBudgetManager;
use Atom\Agent\AgentTask;

class AgentSecurityPassTest extends TestCase
{
    public function testToolRiskEvaluationAndApprovalEnforcement()
    {
        $lowRisk = RiskEngine::evaluateToolRisk('read_file', []);
        $this->assertEquals('low', $lowRisk);
        $this->assertFalse(RiskEngine::requiresHumanApproval($lowRisk));

        $highRisk = RiskEngine::evaluateToolRisk('patch_file', []);
        $this->assertEquals('high', $highRisk);
        $this->assertTrue(RiskEngine::requiresHumanApproval($highRisk));

        $criticalRisk = RiskEngine::evaluateToolRisk('system_exec', ['action' => 'delete table']);
        $this->assertEquals('critical', $criticalRisk);
        $this->assertTrue(RiskEngine::requiresHumanApproval($criticalRisk));
    }

    public function testBudgetManagerEnforcesStepAndTokenLimits()
    {
        $budgetManager = new AgentBudgetManager();
        $task = new AgentTask(['max_steps' => 5, 'max_tokens' => 1000]);

        $task->currentStep = 5;
        $res = $budgetManager->checkBudget($task);
        $this->assertTrue($res['exceeded']);
        $this->assertStringContainsString('BUDGET_EXCEEDED', $res['reason']);
    }
}
