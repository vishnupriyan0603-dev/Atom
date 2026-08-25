<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Document\MarkdownPdfRendererEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 84 — MarkdownPdfRendererEngine unit tests (6 tests).
 */
class MarkdownPdfRendererEngineTest extends TestCase
{
    private MarkdownPdfRendererEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new MarkdownPdfRendererEngine(new SecretRedactor());
    }

    public function testRenderDocumentBasicMarkdown(): void
    {
        $md = "# Project Overview\n\nThis is a sample document.\n\n## Details\n- Fast\n- Secure";
        $res = $this->engine->renderDocument($md, 'Test RFC');

        $this->assertTrue($res['success']);
        $this->assertSame('Test RFC', $res['title']);
        $this->assertGreaterThan(0, $res['word_count']);
        $this->assertSame(1, $res['page_estimate']);
        $this->assertStringContainsString('<h1>Project Overview</h1>', $res['html']);
        $this->assertStringContainsString('<h2>Details</h2>', $res['html']);
        $this->assertStringContainsString('<li>Fast</li>', $res['html']);
    }

    public function testRenderCodeBlocks(): void
    {
        $md = "# Code Spec\n\n```php\n\$x = 10;\n```";
        $res = $this->engine->renderDocument($md);

        $this->assertTrue($res['success']);
        $this->assertStringContainsString('<pre><code>', $res['html']);
        $this->assertStringContainsString('&dollar;x = 10;', str_replace('$', '&dollar;', $res['html']));
    }

    public function testEmptyMarkdownFailsGracefully(): void
    {
        $res = $this->engine->renderDocument('');
        $this->assertFalse($res['success']);
        $this->assertSame(0, $res['page_estimate']);
    }

    public function testMultiPagePaginationEstimate(): void
    {
        // 1200 words should be ~3 pages
        $md = "# Large Spec\n\n" . str_repeat("word lorem ipsum dolor sit amet ", 200);
        $res = $this->engine->renderDocument($md);

        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(2, $res['page_estimate']);
    }

    public function testGetDocumentTemplatesReturnsStandardSpecs(): void
    {
        $templates = $this->engine->getDocumentTemplates();

        $this->assertCount(2, $templates);
        $this->assertSame('arch_rfc', $templates[0]['id']);
        $this->assertSame('security_audit', $templates[1]['id']);
    }

    public function testHtmlSpecialCharsEscapingInOutput(): void
    {
        $md = "Normal text with <script>alert('xss')</script> inside";
        $res = $this->engine->renderDocument($md);

        $this->assertStringNotContainsString("<script>alert('xss')</script>", $res['html']);
        $this->assertStringContainsString("&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;", $res['html']);
    }
}
