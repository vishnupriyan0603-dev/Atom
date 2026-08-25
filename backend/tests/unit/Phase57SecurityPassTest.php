<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Refactoring\OpenApiSdkGeneratorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 57 — Phase57SecurityPassTest security & safety tests (5 tests).
 */
class Phase57SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testOpenApiJsonIsValidJson(): void
    {
        $engine = new OpenApiSdkGeneratorEngine($this->redactor);
        $spec = $engine->generateOpenApiSpec();

        $json = json_encode($spec);
        $this->assertJson($json);
        $this->assertStringNotContainsString('sk-', $json);
    }

    public function testLanguageInjectionSanitization(): void
    {
        $engine = new OpenApiSdkGeneratorEngine($this->redactor);
        // Attempt injection in language parameter
        $maliciousLang = "typescript; rm -rf /; php -r 'eval();'";

        $sdk = $engine->generateSdk($maliciousLang);
        $this->assertTrue($sdk['success']);
        $this->assertSame('typescript', $sdk['language']);
    }

    public function testSdkTemplatesDoNotContainHardcodedSecrets(): void
    {
        $engine = new OpenApiSdkGeneratorEngine($this->redactor);
        $languages = ['typescript', 'python', 'csharp', 'php'];

        foreach ($languages as $lang) {
            $sdk = $engine->generateSdk($lang);
            $this->assertStringNotContainsString('sk-proj-', $sdk['code']);
            $this->assertStringNotContainsString('whsec_', $sdk['code']);
        }
    }

    public function testSdkOutputIsDeterministicAndNonEmpty(): void
    {
        $engine = new OpenApiSdkGeneratorEngine($this->redactor);
        $sdk1 = $engine->generateSdk('python');
        $sdk2 = $engine->generateSdk('python');

        $this->assertSame($sdk1['code'], $sdk2['code']);
        $this->assertGreaterThan(10, $sdk1['lines_of_code']);
    }

    public function testNoDangerousEvalOrShellExecutionInRefactoringSubsystem(): void
    {
        $files = [
            'src/Refactoring/OpenApiSdkGeneratorEngine.php',
            'src/Refactoring/AstPerformanceProfilerEngine.php',
            'src/Refactoring/AstDeadCodeEliminatorEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
