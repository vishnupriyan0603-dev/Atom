<?php

use PHPUnit\Framework\TestCase;
use Atom\Workflow\WorkflowRiskPolicy;
use Atom\Workflow\WorkflowBudgetManager;

class WorkflowSecurityPassTest extends TestCase
{
    public function testWorkflowRiskPolicyNeverDowngradesToolRisk()
    {
        // Tool risk = HIGH, Workflow policy risk = LOW -> Effective risk = HIGH
        $effective = WorkflowRiskPolicy::resolveEffectiveRisk('high', 'low');
        $this->assertEquals('high', $effective);

        // Tool risk = LOW, Workflow policy risk = CRITICAL -> Effective risk = CRITICAL
        $effective2 = WorkflowRiskPolicy::resolveEffectiveRisk('low', 'critical');
        $this->assertEquals('critical', $effective2);
    }

    public function testWorkflowBudgetManagerEnforcesNodeAndRuntimeLimits()
    {
        $budget = new WorkflowBudgetManager(5, 2, 5, 10, 5000, 60);

        $res = $budget->checkBudget(['nodes_executed' => 5]);
        $this->assertTrue($res['exceeded']);
        $this->assertStringContainsString('WORKFLOW_BUDGET_EXCEEDED', $res['reason']);
    }
}
