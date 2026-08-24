<?php

use PHPUnit\Framework\TestCase;
use Atom\Governance\PolicyEngine;
use Atom\Governance\PolicyRule;
use Atom\Governance\Policy;

class PolicyEngineAndResolverTest extends TestCase
{
    public function testPolicyRuleMatchingActionsAndResources()
    {
        $rule = new PolicyRule([
            'action'   => 'tool.*',
            'resource' => 'workspace/*',
            'effect'   => 'allow',
        ]);

        $this->assertTrue($rule->matches('tool.execute', 'workspace/file.txt'));
        $this->assertFalse($rule->matches('tool.execute', 'system/config'));
    }

    public function testPolicyEngineEvaluatesNormalAndHighRiskActions()
    {
        $engine = new PolicyEngine();

        $normal = $engine->evaluate(1, 'tool.read', 'workspace');
        $this->assertTrue($normal->isAllowed());

        $highRisk = $engine->evaluate(2, 'database.drop', 'production_db');
        $this->assertTrue($highRisk->requiresApproval());
    }
}
