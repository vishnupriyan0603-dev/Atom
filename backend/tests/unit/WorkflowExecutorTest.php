<?php

use PHPUnit\Framework\TestCase;
use Atom\Workflow\WorkflowExecutor;

class WorkflowExecutorTest extends TestCase
{
    public function testWorkflowExecutionStartsAndCompletesCleanly()
    {
        $executor = new WorkflowExecutor();
        $execution = $executor->executeWorkflow(1, ['objective' => 'Perform research workflow']);

        $this->assertEquals('completed', $execution->status);
        $this->assertEquals(1, $execution->workflowId);
        $this->assertArrayHasKey('agent', $execution->variables['steps']);
    }
}
