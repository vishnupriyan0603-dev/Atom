<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\NaturalDialogueOrchestratorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 69 — Phase69SecurityPassTest security & safety tests (5 tests).
 */
class Phase69SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInDialogueInput(): void
    {
        $engine = new NaturalDialogueOrchestratorEngine($this->redactor);
        $turn = $engine->processTurn("Hello sk-1122334455667788990011223344 how are you?");

        $this->assertTrue($turn['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $turn['input']);
    }

    public function testNoProviderInternalLeaks(): void
    {
        $engine = new NaturalDialogueOrchestratorEngine($this->redactor);
        $turn = $engine->processTurn("Who is executing this model?");

        $this->assertTrue($turn['success']);
        $this->assertStringNotContainsString('gemini-1.5-pro', $turn['response']);
        $this->assertStringNotContainsString('gpt-4o', $turn['response']);
    }

    public function testHighThroughputDialogueTurns(): void
    {
        $engine = new NaturalDialogueOrchestratorEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->processTurn("Turn {$i} message hello!");
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testNonRoboticToneAssurance(): void
    {
        $engine = new NaturalDialogueOrchestratorEngine($this->redactor);
        $turn = $engine->processTurn("hi");

        $this->assertStringNotContainsString("Certainly! Here is", $turn['response']);
        $this->assertStringNotContainsString("As an AI language model", $turn['response']);
    }

    public function testNoDangerousEvalOrShellExecutionInBrainSubsystem(): void
    {
        $files = [
            'src/Brain/NaturalDialogueOrchestratorEngine.php',
            'src/Brain/PersonalityEngine.php',
            'src/Brain/ContextEngine.php',
            'src/Brain/AwarenessEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
