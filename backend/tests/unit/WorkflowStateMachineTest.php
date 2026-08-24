<?php

use PHPUnit\Framework\TestCase;
use Atom\Workflow\WorkflowStateMachine;

class WorkflowStateMachineTest extends TestCase
{
    public function testValidWorkflowStateTransitions()
    {
        $this->assertTrue(WorkflowStateMachine::canTransition('queued', 'running'));
        $this->assertTrue(WorkflowStateMachine::canTransition('running', 'waiting_approval'));
        $this->assertTrue(WorkflowStateMachine::canTransition('running', 'waiting_delay'));
        $this->assertTrue(WorkflowStateMachine::canTransition('running', 'completed'));
    }

    public function testInvalidWorkflowStateTransitionsReject()
    {
        $this->assertFalse(WorkflowStateMachine::canTransition('completed', 'running'));
        $this->assertFalse(WorkflowStateMachine::canTransition('cancelled', 'retrying'));

        $this->expectException(\InvalidArgumentException::class);
        WorkflowStateMachine::validateTransition('completed', 'running');
    }
}
