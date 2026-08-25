<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\SemanticCodeChunkerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 76 — Phase76SecurityPassTest security & safety tests (5 tests).
 */
class Phase76SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInParsedCodeChunks(): void
    {
        $engine = new SemanticCodeChunkerEngine($this->redactor);
        $code = 'class Config { private $key = "sk-1122334455667788990011223344"; }';

        $res = $engine->splitCodeIntoChunks($code);
        $this->assertTrue($res['success']);

        foreach ($res['chunks'] as $c) {
            $this->assertStringNotContainsString('sk-1122334455667788990011223344', $c['content']);
        }
    }

    public function testHighThroughputLargeCodebaseChunking(): void
    {
        $engine = new SemanticCodeChunkerEngine($this->redactor);
        $largeCode = str_repeat("class Node {\n    public function compute() {\n        return 1;\n    }\n}\n", 100);

        $startTime = microtime(true);
        $res = $engine->splitCodeIntoChunks($largeCode);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(50, $res['total_chunks']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testCallTreeExtractionNeverCrashesOnMalformedSyntax(): void
    {
        $engine = new SemanticCodeChunkerEngine($this->redactor);
        $malformed = '>>> -> -> :: () [[ %%% ;;';

        $tree = $engine->extractCallTree($malformed);
        $this->assertTrue($tree['success']);
        $this->assertIsArray($tree['invoked_symbols']);
    }

    public function testTokenEstimatePositiveAndFinite(): void
    {
        $engine = new SemanticCodeChunkerEngine($this->redactor);
        $res = $engine->splitCodeIntoChunks("class Mini { }");

        $chunk = $res['chunks'][0];
        $this->assertGreaterThan(0, $chunk['token_estimate']);
        $this->assertIsInt($chunk['token_estimate']);
    }

    public function testNoDangerousEvalOrShellExecutionInBrainSubsystem(): void
    {
        $files = [
            'src/Brain/SemanticCodeChunkerEngine.php',
            'src/Brain/NaturalDialogueOrchestratorEngine.php',
            'src/Brain/AtomBrain.php',
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
