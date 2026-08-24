<?php

use PHPUnit\Framework\TestCase;
use Atom\Swarm\SwarmStateMachine;

class SwarmStateMachineTest extends TestCase
{
    public function testValidSwarmStateTransitions()
    {
        $this->assertTrue(SwarmStateMachine::canTransition('queued', 'running'));
        $this->assertTrue(SwarmStateMachine::canTransition('running', 'synthesizing'));
        $this->assertTrue(SwarmStateMachine::canTransition('synthesizing', 'completed'));
    }

    public function testInvalidSwarmStateTransitionsReject()
    {
        $this->assertFalse(SwarmStateMachine::canTransition('completed', 'running'));
        $this->assertFalse(SwarmStateMachine::canTransition('cancelled', 'verifying'));

        $this->expectException(\InvalidArgumentException::class);
        SwarmStateMachine::validateTransition('completed', 'running');
    }
}
