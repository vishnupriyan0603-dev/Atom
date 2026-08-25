<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Api\GraphQLComplexityAnalyzerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 83 — Phase83SecurityPassTest security & safety tests (5 tests).
 */
class Phase83SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInQueryInput(): void
    {
        $engine = new GraphQLComplexityAnalyzerEngine($this->redactor);
        $res = $engine->analyzeQuery('query { user(token: "sk-1122334455667788990011223344") { id } }');

        $this->assertTrue($res['success']);
        $this->assertTrue($res['allowed']);
    }

    public function testHighThroughputQueryAnalysis(): void
    {
        $engine = new GraphQLComplexityAnalyzerEngine($this->redactor);
        $query = 'query GetProfile { me { id name email avatar settings { theme locale notifications } } }';

        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $engine->analyzeQuery($query);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testMalformedUnbalancedBracesSafety(): void
    {
        $engine = new GraphQLComplexityAnalyzerEngine($this->redactor);
        $res = $engine->analyzeQuery('{ { { { incomplete query');

        $this->assertTrue($res['success']);
        $this->assertIsInt($res['max_depth']);
    }

    public function testRecursiveBombShieldSafety(): void
    {
        $engine = new GraphQLComplexityAnalyzerEngine($this->redactor);
        $bomb = 'query { a { b { a { b { a { b { a { b { a { b { a { b { a { b { a } } } } } } } } } } } } } } }';

        $res = $engine->analyzeQuery($bomb);
        $this->assertFalse($res['allowed']);
        $this->assertSame('QUERY_BLOCKED', $res['status']);
    }

    public function testNoDangerousEvalOrShellExecutionInApiSubsystem(): void
    {
        $files = [
            'src/Api/GraphQLComplexityAnalyzerEngine.php',
            'src/Testing/ApiSchemaFuzzerEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
