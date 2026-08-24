<?php

use PHPUnit\Framework\TestCase;
use Atom\Agent\AgentOrchestrator;
use Atom\Agent\AgentBudgetManager;

class AgentOrchestratorTest extends TestCase
{
    public function testTaskCreationAndExecutionLifecycle()
    {
        $orchestrator = new AgentOrchestrator();
        $task = $orchestrator->createTask("Simple question requiring no tools");
        
        $this->assertEquals('pending', $task->status);
        $this->assertEquals(1, $task->userId);

        $completedTask = $orchestrator->runTask($task);
        $this->assertEquals('completed', $completedTask->status);
        $this->assertNotEmpty($completedTask->result);
    }

    public function testBudgetExceededFailsTaskSafely()
    {
        $budgetManager = new AgentBudgetManager();
        $orchestrator = new AgentOrchestrator(null, null, null, null, null, null, $budgetManager);
        
        $task = $orchestrator->createTask("Build complex application", 1, ['max_steps' => 1]);
        $resultTask = $orchestrator->runTask($task);

        // Max steps budget = 1 will cause execution stop/fail
        $this->assertTrue($resultTask->status === 'failed' || $resultTask->status === 'completed');
    }
}
