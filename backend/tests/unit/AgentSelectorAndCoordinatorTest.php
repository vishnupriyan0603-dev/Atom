<?php

use PHPUnit\Framework\TestCase;
use Atom\Swarm\AgentSelector;
use Atom\Swarm\AgentCoordinator;

class AgentSelectorAndCoordinatorTest extends TestCase
{
    public function testAgentSelectorSelectsRoleSpecificDefinitions()
    {
        $selector = new AgentSelector();
        $researcher = $selector->selectAgentForRole('researcher');

        $this->assertEquals('Researcher Agent', $researcher->name);
        $this->assertEquals('worker', $researcher->role);
        $this->assertContains('web_search', $researcher->allowedTools);
    }

    public function testAgentCoordinatorExecutesSwarmTaskCleanly()
    {
        $coordinator = new AgentCoordinator();
        $swarm = $coordinator->runSwarm('Research AI trends');

        $this->assertEquals('completed', $swarm->status);
        $this->assertStringContainsString('Swarm Synthesis Report', $swarm->result);
    }
}
