<?php

use PHPUnit\Framework\TestCase;
use Atom\Testing\SelfCorrectionEngine;

/**
 * Phase 29 — SelfCorrectionEngine unit tests (5 tests).
 */
class SelfCorrectionEngineTest extends TestCase
{
    private SelfCorrectionEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new SelfCorrectionEngine();
    }

    public function testDiagnoseFatalError(): void
    {
        $trace = "Fatal error: Uncaught TypeError: Return value must be string in /app/User.php:25";
        $diag = $this->engine->diagnoseFailure($trace);

        $this->assertTrue($diag['diagnosed']);
        $this->assertSame('Fatal error', $diag['error_type']);
        $this->assertStringContainsString('User.php:25', $diag['location']);
    }

    public function testDiagnoseAssertionFailure(): void
    {
        $trace = "Failed asserting that false is true.";
        $diag = $this->engine->diagnoseFailure($trace);

        $this->assertTrue($diag['diagnosed']);
        $this->assertSame('AssertionFailure', $diag['error_type']);
    }

    public function testSynthesizePatchForAssertionFailure(): void
    {
        $faulty = "public function isReady() { return false; }";
        $error = "Failed asserting that false is true.";

        $patch = $this->engine->synthesizePatch($faulty, $error);
        $this->assertTrue($patch['success']);
        $this->assertSame('AssertionFailure', $patch['error_type']);
        $this->assertStringContainsString('return true;', $patch['patched_code']);
        $this->assertTrue($patch['requires_approval']);
    }

    public function testSynthesizePatchForTypeError(): void
    {
        $faulty = "function checkAccess(\$role) { }";
        $error = "TypeError: Return value must be of type bool";

        $patch = $this->engine->synthesizePatch($faulty, $error);
        $this->assertTrue($patch['success']);
        $this->assertStringContainsString('function checkAccess($role): bool', $patch['patched_code']);
    }

    public function testSynthesizePatchPreservesUnrelatedCode(): void
    {
        $code = "\$config = ['db' => 'mysql'];\nfunction run() { return true; }";
        $error = "Unspecified syntax error";

        $patch = $this->engine->synthesizePatch($code, $error);
        $this->assertTrue($patch['success']);
        $this->assertStringContainsString("\$config = ['db' => 'mysql'];", $patch['patched_code']);
    }
}
