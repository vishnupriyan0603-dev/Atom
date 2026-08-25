<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\SemanticCodeChunkerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 76 — SemanticCodeChunkerEngine unit tests (6 tests).
 */
class SemanticCodeChunkerEngineTest extends TestCase
{
    private SemanticCodeChunkerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SemanticCodeChunkerEngine(new SecretRedactor());
    }

    public function testSplitPhpClassAndMethodsIntoChunks(): void
    {
        $code = "<?php\nclass UserEngine {\n    public function login(): bool {\n        return true;\n    }\n    public function logout(): void {\n        \$this->cleanup();\n    }\n}";
        $res = $this->engine->splitCodeIntoChunks($code, 'php');

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(0, $res['total_chunks']);
        $symbols = array_column($res['chunks'], 'symbol_name');
        $this->assertContains('login', $symbols);
        $this->assertGreaterThan(0, $res['chunks'][0]['token_estimate']);
    }

    public function testExtractCallTreeFromMethodInvocations(): void
    {
        $code = '$this->verifyAuth(); Database::query("SELECT 1"); $user->save();';
        $tree = $this->engine->extractCallTree($code);

        $this->assertTrue($tree['success']);
        $this->assertContains('verifyAuth', $tree['invoked_symbols']);
        $this->assertContains('query', $tree['invoked_symbols']);
        $this->assertContains('save', $tree['invoked_symbols']);
    }

    public function testEmptySourceCodeFailsGracefully(): void
    {
        $res = $this->engine->splitCodeIntoChunks('');
        $this->assertFalse($res['success']);
        $this->assertSame(0, $res['total_chunks']);
    }

    public function testSplitPythonDefFunctions(): void
    {
        $pyCode = "def authenticate(user, password):\n    return True\n\ndef logout():\n    pass";
        $res = $this->engine->splitCodeIntoChunks($pyCode, 'python');

        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(1, $res['total_chunks']);
    }

    public function testTokenEstimateCalculationAccuracy(): void
    {
        $code = "class A { function test() { echo 'Hello World'; } }";
        $res = $this->engine->splitCodeIntoChunks($code, 'php');

        $chunk = $res['chunks'][0];
        $this->assertGreaterThan(0, $chunk['token_estimate']);
        $this->assertSame(strlen($code), strlen($chunk['content']));
    }

    public function testDeduplicationInExtractedCallTree(): void
    {
        $code = '$this->run(); $this->run(); $this->run();';
        $tree = $this->engine->extractCallTree($code);

        $this->assertSame(1, $tree['distinct_calls_found']);
        $this->assertSame(['run'], $tree['invoked_symbols']);
    }
}
