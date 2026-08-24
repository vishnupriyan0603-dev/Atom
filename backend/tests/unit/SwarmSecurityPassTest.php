<?php

use PHPUnit\Framework\TestCase;
use Atom\Swarm\SwarmBudgetManager;

class SwarmSecurityPassTest extends TestCase
{
    public function testSwarmBudgetManagerEnforcesAgentCountAndDepthLimits()
    {
        $budget = new SwarmBudgetManager(8, 3, 100, 20, 30, 50000, 3600);

        $res = $budget->checkBudget(['agents' => 8]);
        $this->assertTrue($res['exceeded']);
        $this->assertStringContainsString('SWARM_BUDGET_EXCEEDED', $res['reason']);

        $res2 = $budget->checkBudget(['depth' => 3]);
        $this->assertTrue($res2['exceeded']);
        $this->assertStringContainsString('SWARM_BUDGET_EXCEEDED', $res2['reason']);
    }
}
