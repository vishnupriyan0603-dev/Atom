<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomBrainFactory;

require_once dirname(__DIR__, 2) . '/../src/Brain/AtomBrainFactory.php';

/**
 * Unit test for AtomBrain ARC Autonomous Control interception.
 */
class AtomBrainArcControlTest extends TestCase
{
    private $brain;

    protected function setUp(): void
    {
        parent::setUp();
        $workspace = str_replace('\\', '/', dirname(__DIR__, 3));
        $this->brain = AtomBrainFactory::create($workspace);
    }

    public function testBrainExecutesNotepadActionDirectly(): void
    {
        $history = [];
        $response = $this->brain->process('open notepad type Hi vichu', $history);

        $this->assertStringContainsString('ATOM [ARC Control System]', $response);
        $this->assertStringContainsString('Opened Notepad (notepad.exe)', $response);
        $this->assertStringContainsString('Hi vichu', $response);
        $this->assertStringNotContainsString('Press Win + R', $response);
        $this->assertStringNotContainsString('Press **Win', $response);

        // Clean up process
        $this->brain->process('close notepad', $history);
    }

    public function testBrainExecutesTypeInNotepadCommand(): void
    {
        $history = [];
        $response = $this->brain->process('type Hello Atom in notepad', $history);

        $this->assertStringContainsString('ATOM [ARC Control System]', $response);
        $this->assertStringContainsString('notepad.exe', $response);

        // Clean up process
        $this->brain->process('close notepad', $history);
    }
}

