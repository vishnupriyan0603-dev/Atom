<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\IntentEngine;
use Atom\Brain\IntentResult;

/**
 * Phase 23 — IntentEngine unit tests (8 tests).
 */
class BrainIntentEngineTest extends TestCase
{
    private IntentEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new IntentEngine();
    }

    public function testGreetingClassification(): void
    {
        $result = $this->engine->classify('hello');
        $this->assertSame('greeting', $result->intent);
        $this->assertSame('local', $result->routingHint);
        $this->assertGreaterThanOrEqual(90, $result->confidence);
    }

    public function testCodingClassification(): void
    {
        $result = $this->engine->classify('fix my login.php bug');
        $this->assertSame('coding', $result->intent);
        $this->assertSame('llm', $result->routingHint);
        $this->assertGreaterThanOrEqual(70, $result->confidence);
    }

    public function testWorkflowTriggerClassification(): void
    {
        $result = $this->engine->classify('trigger workflow deployment');
        $this->assertSame('workflow_trigger', $result->intent);
        $this->assertSame('workflow', $result->routingHint);
    }

    public function testSwarmDispatchClassification(): void
    {
        $result = $this->engine->classify('spawn swarm agents for analysis');
        $this->assertSame('swarm_dispatch', $result->intent);
        $this->assertSame('swarm', $result->routingHint);
    }

    public function testGovernanceQueryClassification(): void
    {
        $result = $this->engine->classify('show governance policies');
        $this->assertSame('governance_query', $result->intent);
        $this->assertSame('governance', $result->routingHint);
    }

    public function testMemoryCommandClassification(): void
    {
        $result = $this->engine->classify('remember that I prefer CodeIgniter 4');
        $this->assertSame('memory_command', $result->intent);
        $this->assertSame('local', $result->routingHint);
        $this->assertArrayHasKey('preference', $result->entities);
    }

    public function testEntityExtractionForCodingIntent(): void
    {
        $result = $this->engine->classify('fix the bug in controllers/Login.php');
        $this->assertArrayHasKey('file', $result->entities);
    }

    public function testFallbackToConversation(): void
    {
        $result = $this->engine->classify('');
        $this->assertSame('conversation', $result->intent);
    }
}
