<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Document\MarkdownPdfRendererEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 84 — Phase84SecurityPassTest security & safety tests (5 tests).
 */
class Phase84SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInDocumentMarkdownAndTitle(): void
    {
        $engine = new MarkdownPdfRendererEngine($this->redactor);
        $res = $engine->renderDocument(
            'Secret token in body: sk-1122334455667788990011223344',
            'Confidential sk-1122334455667788990011223344 Spec'
        );

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['html']);
        $this->assertStringNotContainsString('sk-1122334455667788990011223344', $res['title']);
    }

    public function testHighThroughputDocumentCompilation(): void
    {
        $engine = new MarkdownPdfRendererEngine($this->redactor);
        $md = "# Benchmark Document\n\n- Point 1\n- Point 2\n\n```php\n\$var = true;\n```";

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $engine->renderDocument($md, "Doc {$i}");
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testPageCountNeverZeroForValidContent(): void
    {
        $engine = new MarkdownPdfRendererEngine($this->redactor);
        $res = $engine->renderDocument("Just one sentence.");

        $this->assertGreaterThanOrEqual(1, $res['page_estimate']);
    }

    public function testXssInjectionNeutralizedInAllMarkdownNodes(): void
    {
        $engine = new MarkdownPdfRendererEngine($this->redactor);
        $res = $engine->renderDocument("# <img src=x onerror=alert(1)>\n\n> <iframe src='evil.com'></iframe>");

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $res['html']);
        $this->assertStringNotContainsString("<iframe src='evil.com'></iframe>", $res['html']);
    }

    public function testNoDangerousEvalOrShellExecutionInDocumentSubsystem(): void
    {
        $files = [
            'src/Document/MarkdownPdfRendererEngine.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
