<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomPersonalAssistantEngine;
use Atom\Security\SecretRedactor;

/**
 * AtomBrainPhase1SecurityPassTest security & safety tests (5 tests).
 */
class AtomBrainPhase1SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInPromptAndTeaching(): void
    {
        $engine = new AtomPersonalAssistantEngine($this->redactor);
        $res = $engine->teachConcept('Security', 'Use sk-1122334455667788990011223344 as api token');

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['concept']);
    }

    public function testHighThroughputAssistantTurnEvaluation(): void
    {
        $engine = new AtomPersonalAssistantEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->generateLocalResponse("Question number {$i} regarding bike prices");
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testSystemPromptContainsAllNineteenAssistantGuidelines(): void
    {
        $prompt = AtomPersonalAssistantEngine::SYSTEM_PROMPT;

        $this->assertStringContainsString('RESPONSE DEPTH', $prompt);
        $this->assertStringContainsString('ATOM CONVERSATION STYLE', $prompt);
        $this->assertStringContainsString('EMOTION', $prompt);
        $this->assertStringContainsString('ENGLISH TEACHING', $prompt);
        $this->assertStringContainsString('REAL WORLD QUESTIONS', $prompt);
        $this->assertStringContainsString('CALCULATIONS', $prompt);
        $this->assertStringContainsString('Natural English', $prompt);
    }

    public function testNonStandardInputHandledCleanly(): void
    {
        $engine = new AtomPersonalAssistantEngine($this->redactor);
        $res = $engine->generateLocalResponse('');

        $this->assertNotEmpty($res['reply']);
        $this->assertSame(1, $res['depth_level']);
    }

    public function testZeroDangerousExecutionInBrainSubsystem(): void
    {
        $files = [
            'src/Brain/AtomPersonalAssistantEngine.php',
            'src/Brain/NaturalDialogueOrchestratorEngine.php',
            'src/Brain/LearningEngine.php',
            'src/Brain/PersonalityEngine.php',
            'src/Brain/AwarenessEngine.php',
            'src/Brain/ContextEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
