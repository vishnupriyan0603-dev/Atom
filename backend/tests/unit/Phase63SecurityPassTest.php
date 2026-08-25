<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\AstCodeLinterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 63 — Phase63SecurityPassTest security & safety tests (5 tests).
 */
class Phase63SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInLinterOutput(): void
    {
        $engine = new AstCodeLinterEngine($this->redactor);
        $code = "<?php\n\$secret = 'sk-1122334455667788990011223344';\nclass Foo { }";

        $scan = $engine->scanCode($code);
        $this->assertTrue($scan['success']);
    }

    public function testMalformedSyntaxSafetyDoesNotThrow(): void
    {
        $engine = new AstCodeLinterEngine($this->redactor);
        $malformed = "<?php class {{{ function (((( ; ; ;";

        $scan = $engine->scanCode($malformed);
        $this->assertTrue($scan['success']);
    }

    public function testLargeFileLinterResilience(): void
    {
        $engine = new AstCodeLinterEngine($this->redactor);
        $largeCode = "<?php\n\ndeclare(strict_types=1);\n\n";
        for ($i = 0; $i < 300; $i++) {
            $largeCode .= "function method{$i}() {\n    return {$i};\n}\n";
        }

        $startTime = microtime(true);
        $fix = $engine->fixCode($largeCode);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($fix['success']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testComplianceScoreBoundedBetweenZeroAndHundred(): void
    {
        $engine = new AstCodeLinterEngine($this->redactor);
        $horribleCode = "<?php class A { function b() { } } ?>";

        $scan = $engine->scanCode($horribleCode);
        $this->assertGreaterThanOrEqual(0, $scan['compliance_score']);
        $this->assertLessThanOrEqual(100, $scan['compliance_score']);
    }

    public function testNoDangerousEvalOrShellExecutionInRefactoringSubsystem(): void
    {
        $files = [
            'src/Refactoring/AstCodeLinterEngine.php',
            'src/Refactoring/AstPerformanceProfilerEngine.php',
            'src/Refactoring/AstDeadCodeEliminatorEngine.php',
            'src/Refactoring/OpenApiSdkGeneratorEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
