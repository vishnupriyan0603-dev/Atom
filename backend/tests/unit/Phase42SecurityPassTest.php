<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Vision\NeuralCodeOcrEngine;
use Atom\Vision\VisualLayoutSynthesizer;
use Atom\Vision\DiagramSchemaSynthesizer;
use Atom\Security\SecretRedactor;

/**
 * Phase 42 — Phase42SecurityPassTest security & safety tests (5 tests).
 */
class Phase42SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInOcrCode(): void
    {
        $engine = new NeuralCodeOcrEngine($this->redactor);
        $code = "const apiKey = 'sk-proj-super-secret-api-key-998877';\nconst token = 'ghp_github_personal_token_12345';";
        $result = $engine->extractCode($code);

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('sk-proj-super-secret-api-key-998877', $result['code']);
        $this->assertStringNotContainsString('ghp_github_personal_token_12345', $result['code']);
    }

    public function testSecretRedactionInUiLayoutCode(): void
    {
        $synthesizer = new VisualLayoutSynthesizer($this->redactor);
        $result = $synthesizer->synthesize([
            'title' => 'Admin Panel sk-live-master-key-000000',
            'components' => [
                ['type' => 'card', 'label' => 'Secret Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.secret'],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('sk-live-master-key-000000', $result['generated_code']);
    }

    public function testSecretRedactionInDiagramSchema(): void
    {
        $synthesizer = new DiagramSchemaSynthesizer($this->redactor);
        $result = $synthesizer->synthesize("[VaultKeys]\n- id: int\n- token_sk-live-1234567890abcdef1234567890: varchar");

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('sk-live-1234567890abcdef1234567890', $result['sql_ddl']);
    }

    public function testXssInjectionSafetyInMockupTitle(): void
    {
        $synthesizer = new VisualLayoutSynthesizer($this->redactor);
        $result = $synthesizer->synthesize([
            'title' => '<script>alert("XSS")</script>',
            'framework' => 'bootstrap5',
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $result['generated_code']);
        $this->assertStringContainsString('&lt;script&gt;', $result['generated_code']);
    }

    public function testNoDangerousEvalOrShellExecutionInVisionSubsystem(): void
    {
        $files = [
            'src/Vision/NeuralCodeOcrEngine.php',
            'src/Vision/VisualLayoutSynthesizer.php',
            'src/Vision/DiagramSchemaSynthesizer.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}

