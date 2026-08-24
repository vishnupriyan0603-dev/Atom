<?php

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\RefactorSafetyVerifier;

/**
 * Phase 35 — RefactorSafetyVerifier unit tests (5 tests).
 */
class RefactorSafetyVerifierTest extends TestCase
{
    private RefactorSafetyVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new RefactorSafetyVerifier();
    }

    public function testValidRefactoredCodePasses(): void
    {
        $original = 'class Test { public function execute() { return true; } }';
        $refactored = 'class Test { public function execute() { return $this->helper(); } private function helper() { return true; } }';

        $res = $this->verifier->verify($original, $refactored);

        $this->assertTrue($res['safe']);
        $this->assertTrue($res['syntax_valid']);
        $this->assertEmpty($res['violations']);
    }

    public function testUnbalancedBracesFailVerification(): void
    {
        $original = 'class Test { public function execute() { return true; } }';
        $broken = 'class Test { public function execute() { return true; } '; // Missing closing brace

        $res = $this->verifier->verify($original, $broken);

        $this->assertFalse($res['safe']);
        $this->assertNotEmpty($res['violations']);
        $this->assertStringContainsString('Unbalanced curly braces', $res['violations'][0]);
    }

    public function testMissingPublicApiMethodFailsVerification(): void
    {
        $original = 'class Api { public function doImportantTask() {} }';
        $altered = 'class Api { public function differentMethod() {} }'; // Removed doImportantTask

        $res = $this->verifier->verify($original, $altered);

        $this->assertFalse($res['safe']);
        $this->assertStringContainsString("Public API invariant violated: method 'doImportantTask'", $res['violations'][0]);
    }

    public function testEmptyRefactoredCodeFails(): void
    {
        $res = $this->verifier->verify('class Original {}', '');

        $this->assertFalse($res['safe']);
        $this->assertFalse($res['syntax_valid']);
    }

    public function testUnbalancedParenthesesFail(): void
    {
        $original = 'class Test { public function run() {} }';
        $broken = 'class Test { public function run() { if (true { return 1; } } }';

        $res = $this->verifier->verify($original, $broken);

        $this->assertFalse($res['safe']);
        $this->assertStringContainsString('Unbalanced parentheses', $res['violations'][0]);
    }
}
