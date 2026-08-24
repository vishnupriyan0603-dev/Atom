<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\ContextEngine;

/**
 * Phase 23 — ContextEngine unit tests (6 tests).
 */
class BrainContextEngineTest extends TestCase
{
    public function testInitialStatIsEmpty(): void
    {
        $engine = new ContextEngine();
        $summary = $engine->getSummary();
        $this->assertSame('', $summary['active_topic']);
        $this->assertSame('', $summary['inferred_goal']);
        $this->assertEmpty($summary['referenced_entities']);
        $this->assertSame(0, $summary['turn_count']);
    }

    public function testUpdateIncreasesTurnCount(): void
    {
        $engine = new ContextEngine();
        $engine->update('fix my login', 'Here is the fix', 'coding');
        $this->assertSame(1, $engine->getTurnCount());
    }

    public function testCodingUpdateSetsCorrectTopic(): void
    {
        $engine = new ContextEngine();
        $engine->update('debug this function', 'Sure!', 'coding');
        $this->assertSame('Software Development', $engine->getActiveTopic());
    }

    public function testEntityExtractionFromFileReference(): void
    {
        $engine = new ContextEngine();
        $engine->update('read the file login.php please', 'Sure', 'coding');
        $entities = $engine->getReferencedEntities();
        $this->assertNotEmpty($entities);
        $this->assertTrue(in_array('login.php', $entities, true));
    }

    public function testContextBlockIsEmptyWhenNoContext(): void
    {
        $engine = new ContextEngine();
        $this->assertSame('', $engine->buildContextBlock());
    }

    public function testResetClearsAllState(): void
    {
        $engine = new ContextEngine();
        $engine->update('fix my code', 'Done', 'coding');
        $engine->reset();
        $this->assertSame('', $engine->getActiveTopic());
        $this->assertSame(0, $engine->getTurnCount());
        $this->assertEmpty($engine->getReferencedEntities());
    }
}
