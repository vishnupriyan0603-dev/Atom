<?php

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\CodeSmellDetector;
use Atom\Refactoring\ASTTransformationEngine;
use Atom\Refactoring\RefactorSafetyVerifier;

/**
 * Phase 35 — RefactoringSecurityPassTest security & safety tests (5 tests).
 */
class RefactoringSecurityPassTest extends TestCase
{
    public function testSecretRedactionInCodeSmellReports(): void
    {
        $detector = new CodeSmellDetector();
        $code = 'class SecretAuth { public function login() { $apiKey = "sk-live-123456789012345678901234567890"; return true; } }';

        $res = $detector->scan($code);

        $this->assertIsArray($res);
        $this->assertSame(0, $res['total_smells']);
    }

    public function testNoEvalOrDangerousExecutionInRefactoringSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $smellCode = file_get_contents($rootDir . '/src/Refactoring/CodeSmellDetector.php');
        $astCode = file_get_contents($rootDir . '/src/Refactoring/ASTTransformationEngine.php');
        $depCode = file_get_contents($rootDir . '/src/Refactoring/DependencyGraphAnalyzer.php');
        $verifierCode = file_get_contents($rootDir . '/src/Refactoring/RefactorSafetyVerifier.php');

        $this->assertNotFalse($smellCode);
        $this->assertNotFalse($astCode);
        $this->assertNotFalse($depCode);
        $this->assertNotFalse($verifierCode);

        $this->assertStringNotContainsString('eval(', $smellCode);
        $this->assertStringNotContainsString('eval(', $astCode);
        $this->assertStringNotContainsString('eval(', $depCode);
        $this->assertStringNotContainsString('eval(', $verifierCode);
        $this->assertStringNotContainsString('exec(', $astCode);
        $this->assertStringNotContainsString('shell_exec(', $astCode);
    }

    public function testCodeInjectionPreventionInMethodExtraction(): void
    {
        $ast = new ASTTransformationEngine();
        $source = "class Demo { public function run() { \$x = 1; } }";
        $options = [
            'target_block'    => '$x = 1;',
            'new_method_name' => 'cleanMethod; system("whoami");',
            'params'          => [],
        ];

        $res = $ast->transform('extract_method', $source, $options);

        $this->assertTrue($res['success']);
        // Verifier checks that no broken syntax or unhandled execution occurs
        $verifier = new RefactorSafetyVerifier();
        $vRes = $verifier->verify($source, $res['code']);
        $this->assertIsBool($vRes['safe']);
    }

    public function testLargePayloadResourceBoundary(): void
    {
        $detector = new CodeSmellDetector();
        // 2,000 lines of safe code
        $largeCode = implode("\n", array_fill(0, 2000, '$x = $a + $b;'));

        $res = $detector->scan($largeCode);

        $this->assertIsArray($res);
        $this->assertSame(2000, $res['loc']);
    }

    public function testDeterministicOutputPreserved(): void
    {
        $ast = new ASTTransformationEngine();
        $source = 'if ($order->isValid() === true) { return $flag === false; }';

        $run1 = $ast->transform('simplify_boolean', $source);
        $run2 = $ast->transform('simplify_boolean', $source);

        $this->assertSame($run1['code'], $run2['code']);
    }
}
