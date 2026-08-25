<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\AstPerformanceProfilerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 51 — Phase51SecurityPassTest security & safety tests (5 tests).
 */
class Phase51SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInProfilingCode(): void
    {
        $engine = new AstPerformanceProfilerEngine($this->redactor);
        $code = "\$token = 'sk-1122334455667788990011223344';\nforeach (\$a as \$x) { foreach (\$b as \$y) { } }";

        $analysis = $engine->analyze($code);
        $this->assertTrue($analysis['success']);
    }

    public function testReDoSSafetyOnLargeInputFiles(): void
    {
        $engine = new AstPerformanceProfilerEngine($this->redactor);
        $largeCode = str_repeat("foreach (\$items as \$i) { \$count++; }\n", 500);

        $startTime = microtime(true);
        $analysis = $engine->analyze($largeCode);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($analysis['success']);
        $this->assertLessThan(1.0, $duration); // Must finish within 1 second
    }

    public function testScoreNeverExceedsBounds(): void
    {
        $engine = new AstPerformanceProfilerEngine($this->redactor);
        $manyBottlenecks = str_repeat("foreach (\$a as \$x) { foreach (\$b as \$y) {} }\n", 20);

        $analysis = $engine->analyze($manyBottlenecks);

        $this->assertGreaterThanOrEqual(10.0, $analysis['performance_score']);
        $this->assertLessThanOrEqual(100.0, $analysis['performance_score']);
    }

    public function testPreservesUnrelatedCodeDuringOptimization(): void
    {
        $engine = new AstPerformanceProfilerEngine($this->redactor);
        $code = "// Critical invoice generator\nfunction computeTotal() { return 100; }\n\nforeach (\$a as \$x) { foreach (\$b as \$y) { if (\$x['id'] === \$y['id']) { \$res[] = \$x; } } }";

        $opt = $engine->optimize($code);

        $this->assertTrue($opt['success']);
        $this->assertStringContainsString('function computeTotal()', $opt['optimized_code']);
    }

    public function testNoDangerousEvalOrShellExecutionInRefactoringSubsystem(): void
    {
        $files = [
            'src/Refactoring/AstPerformanceProfilerEngine.php',
            'src/Refactoring/AstCodeModernizerEngine.php',
            'src/Refactoring/OwaspAutoPatcherEngine.php',
            'src/Refactoring/DependencyGraphEngine.php',
            'src/Refactoring/DecouplingSynthesizer.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
