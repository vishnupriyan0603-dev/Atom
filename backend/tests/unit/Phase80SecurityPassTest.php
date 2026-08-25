<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\MetacognitiveReasoningEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 80 Landmark — Phase80SecurityPassTest security & safety tests (5 tests).
 */
class Phase80SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInReasoningStepsAndGoal(): void
    {
        $engine = new MetacognitiveReasoningEngine($this->redactor);
        $res = $engine->reflectOnThoughtChain(
            ['Step 1: Check token sk-1122334455667788990011223344 authorization'],
            'Goal sk-1122334455667788990011223344 verify'
        );

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['goal']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['steps'][0]['thought']);
    }

    public function testHighThroughputMetacognitiveReflection(): void
    {
        $engine = new MetacognitiveReasoningEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 200; $i++) {
            $engine->reflectOnThoughtChain([
                "Step {$i}a: Evaluate hypothesis {$i}",
                "Step {$i}b: Ground facts with knowledge items",
                "Step {$i}c: Deliver verified conclusion"
            ]);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testClarityScoreBoundedBetweenZeroAndHundred(): void
    {
        $engine = new MetacognitiveReasoningEngine($this->redactor);
        $res = $engine->reflectOnThoughtChain(['Invalid single step']);

        $this->assertGreaterThanOrEqual(0.0, $res['metacognitive_clarity_pct']);
        $this->assertLessThanOrEqual(100.0, $res['metacognitive_clarity_pct']);
    }

    public function testPruneThoughtGraphEmptyOrMalformedSafety(): void
    {
        $engine = new MetacognitiveReasoningEngine($this->redactor);
        $res = $engine->pruneThoughtGraph([]);

        $this->assertTrue($res['success']);
        $this->assertSame(0, $res['total_nodes']);
    }

    public function testNoDangerousEvalOrShellExecutionInBrainSubsystem(): void
    {
        $files = [
            'src/Brain/MetacognitiveReasoningEngine.php',
            'src/Brain/SemanticCodeChunkerEngine.php',
            'src/Brain/NaturalDialogueOrchestratorEngine.php',
            'src/Brain/AwarenessEngine.php',
            'src/Brain/PersonalityEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
