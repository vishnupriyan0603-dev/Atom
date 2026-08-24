<?php

use PHPUnit\Framework\TestCase;
use Atom\Agent\Planner;
use Atom\Agent\PlanValidator;
use Atom\Agent\AgentTask;

class PlannerAndValidatorTest extends TestCase
{
    public function testPlanGenerationAndValidationSuccess()
    {
        $planner = new Planner();
        $validator = new PlanValidator();
        $task = new AgentTask(['objective' => 'Research CodeIgniter 4 migration guide', 'max_steps' => 10]);

        $plan = $planner->generatePlan($task->objective);
        $this->assertNotEmpty($plan['steps']);

        $res = $validator->validatePlan($plan, $task);
        $this->assertTrue($res['valid']);
    }

    public function testValidatorRejectsForbiddenOperationsAndExcessSteps()
    {
        $validator = new PlanValidator();
        $task = new AgentTask(['objective' => 'Test task', 'max_steps' => 2]);

        $invalidPlan = [
            'steps' => [
                ['sequence' => 1, 'type' => 'reasoning', 'description' => 'rm -rf / system command'],
                ['sequence' => 2, 'type' => 'reasoning', 'description' => 'Step 2'],
                ['sequence' => 3, 'type' => 'reasoning', 'description' => 'Step 3'],
            ]
        ];

        $res = $validator->validatePlan($invalidPlan, $task);
        $this->assertFalse($res['valid']);
    }
}
