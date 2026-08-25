<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\AstCodeModernizerEngine;
use Atom\Refactoring\OwaspAutoPatcherEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 47 — Phase47SecurityPassTest security & safety tests (5 tests).
 */
class Phase47SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInModernizationCode(): void
    {
        $engine = new AstCodeModernizerEngine($this->redactor);
        $codeWithSecret = "\$apiKey = 'sk-1122334455667788990011223344';\nif (strpos(\$val, 'a') !== false) { return 1; }";

        $result = $engine->modernize($codeWithSecret);

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $result['modernized_code']);
    }

    public function testSecretRedactionInOwaspSecurityScanner(): void
    {
        $patcher = new OwaspAutoPatcherEngine($this->redactor);
        $code = "\$token = 'sk-99887766554433221100';\necho \$_GET['q'];";

        $scan = $patcher->scan($code);
        $this->assertNotEmpty($scan['vulnerabilities']);
    }

    public function testPreservesUnrelatedCodeDuringAutoPatch(): void
    {
        $patcher = new OwaspAutoPatcherEngine($this->redactor);
        $code = "// Critical payment calculation\nfunction calculateTax(\$subtotal) { return \$subtotal * 0.18; }\n\necho \$_GET['receipt_id'];";

        $patch = $patcher->autoPatch($code);

        $this->assertTrue($patch['success']);
        $this->assertStringContainsString('function calculateTax($subtotal)', $patch['patched_code']);
        $this->assertStringContainsString('htmlspecialchars', $patch['patched_code']);
    }

    public function testMultiVulnerabilityBatchPatching(): void
    {
        $patcher = new OwaspAutoPatcherEngine($this->redactor);
        $multiVulnCode = "\$user = \$db->query(\"SELECT * FROM users WHERE id = \" . \$id);\necho \$_GET['search'];\n\$f = file_get_contents(\$_GET['file']);";

        $result = $patcher->autoPatch($multiVulnCode);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['patches_applied_count']);
        $this->assertSame(0, $result['remaining_vulnerabilities']);
    }

    public function testNoDangerousEvalOrShellExecutionInRefactoringSubsystem(): void
    {
        $files = [
            'src/Refactoring/AstCodeModernizerEngine.php',
            'src/Refactoring/OwaspAutoPatcherEngine.php',
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
