<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\DependencyGraphEngine;
use Atom\Refactoring\DecouplingSynthesizer;
use Atom\Security\SecretRedactor;

/**
 * Phase 43 — Phase43SecurityPassTest security & safety tests (5 tests).
 */
class Phase43SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInDependencyGraphParsing(): void
    {
        $engine = new DependencyGraphEngine($this->redactor);
        $code = "<?php\nnamespace Atom\\Sec;\n// sk-live-secret-99887766554433221100\nclass SafeService {\n    public function connect() {}\n}";
        $parsed = $engine->parseDependenciesFromCode($code);

        $this->assertSame('Atom\\Sec\\SafeService', $parsed['class_name']);
    }

    public function testSecretRedactionInDecouplingPatchCode(): void
    {
        $synthesizer = new DecouplingSynthesizer($this->redactor);
        $cycle = ['ServiceWithSecret_sk-live-00112233445566778899', 'OtherService', 'ServiceWithSecret_sk-live-00112233445566778899'];
        $result = $synthesizer->synthesizeDecoupling($cycle);

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('sk-live-00112233445566778899', $result['patch_code']);
    }

    public function testCircularRecursionStackOverflowSafety(): void
    {
        $engine = new DependencyGraphEngine($this->redactor);
        // Create large nested circular chain
        $largeCyclicGraph = [];
        for ($i = 0; $i < 50; $i++) {
            $next = ($i + 1) % 50;
            $largeCyclicGraph["Node_{$i}"] = ["Node_{$next}"];
        }

        $result = $engine->analyzeGraph($largeCyclicGraph);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['has_cycles']);
        $this->assertLessThanOrEqual(50, count($result['circular_cycles']));
    }

    public function testCodeInjectionSanitizationInMermaidDiagram(): void
    {
        $engine = new DependencyGraphEngine($this->redactor);
        $graph = [
            'Class<script>alert(1)</script>' => ['Target"}]-->>evil'],
        ];

        $result = $engine->analyzeGraph($graph);

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('<script>', $result['mermaid_diagram']);
    }

    public function testNoDangerousEvalOrShellExecutionInRefactoringSubsystem(): void
    {
        $files = [
            'src/Refactoring/DependencyGraphEngine.php',
            'src/Refactoring/DecouplingSynthesizer.php',
            'src/Refactoring/DependencyGraphAnalyzer.php',
            'src/Refactoring/ASTTransformationEngine.php',
            'src/Refactoring/CodeSmellDetector.php',
            'src/Refactoring/RefactorSafetyVerifier.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
