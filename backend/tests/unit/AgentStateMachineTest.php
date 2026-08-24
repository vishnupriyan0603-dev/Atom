<?php

use PHPUnit\Framework\TestCase;
use Atom\Agent\AgentStateMachine;

class AgentStateMachineTest extends TestCase
{
    public function testValidStateTransitions()
    {
        $this->assertTrue(AgentStateMachine::canTransition('pending', 'planning'));
        $this->assertTrue(AgentStateMachine::canTransition('planning', 'planned'));
        $this->assertTrue(AgentStateMachine::canTransition('planned', 'running'));
        $this->assertTrue(AgentStateMachine::canTransition('running', 'waiting_approval'));
        $this->assertTrue(AgentStateMachine::canTransition('running', 'verifying'));
        $this->assertTrue(AgentStateMachine::canTransition('verifying', 'completed'));
    }

    public function testInvalidStateTransitionsReject()
    {
        $this->assertFalse(AgentStateMachine::canTransition('completed', 'running'));
        $this->assertFalse(AgentStateMachine::canTransition('cancelled', 'planning'));
        
        $this->expectException(\InvalidArgumentException::class);
        AgentStateMachine::validateTransition('completed', 'planning');
    }
}
