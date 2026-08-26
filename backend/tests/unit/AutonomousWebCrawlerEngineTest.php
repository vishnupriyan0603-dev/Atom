<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Search\AutonomousWebCrawlerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 103 — AutonomousWebCrawlerEngine unit tests (6 tests).
 */
class AutonomousWebCrawlerEngineTest extends TestCase
{
    private AutonomousWebCrawlerEngine $crawler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new AutonomousWebCrawlerEngine(new SecretRedactor());
    }

    public function testSinglePageHtmlDomParsingAndMetadata(): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>PHP 8.3 Performance Guide</title>
    <meta name="description" content="Comprehensive benchmarks and optimizations for PHP 8.3.">
</head>
<body>
    <header><nav><a href="/home">Home</a></nav></header>
    <main>
        <h1>PHP 8.3 Architecture</h1>
        <p>PHP 8.3 introduces typed class constants, dynamic class constant fetch, and json_validate.</p>
        <h2>Benchmarking Code</h2>
        <pre><code>function benchmark() { return json_validate('{"test":1}'); }</code></pre>
    </main>
    <footer><p>&copy; 2026 Developer Mesh</p></footer>
</body>
</html>
HTML;

        $res = $this->crawler->extractPageContent($html, 'https://example.com/guide.html');
        $this->assertEquals('PHP 8.3 Performance Guide', $res['title']);
        $this->assertEquals('Comprehensive benchmarks and optimizations for PHP 8.3.', $res['meta_desc']);
        $this->assertCount(2, $res['headings']);
        $this->assertEquals('PHP 8.3 Architecture', $res['headings'][0]['text']);
        $this->assertCount(1, $res['code_blocks']);
        $this->assertStringContainsString('json_validate', $res['code_blocks'][0]);
        $this->assertStringContainsString('typed class constants', $res['clean_text']);
        // Header, nav, and footer should be stripped from clean_text
        $this->assertStringNotContainsString('Developer Mesh', $res['clean_text']);
    }

    public function testRecursiveLinkDiscoveryAndResolution(): void
    {
        $html = <<<HTML
<html>
<body>
    <a href="/docs/intro.html">Intro</a>
    <a href="tutorials/setup.html">Setup</a>
    <a href="https://example.com/api/v1">API Reference</a>
    <a href="#section-2">Anchor</a>
    <a href="javascript:void(0)">Invalid</a>
</body>
</html>
HTML;

        $res = $this->crawler->extractPageContent($html, 'https://example.com/docs/index.html');
        $this->assertCount(3, $res['outbound_links']);
        $this->assertContains('https://example.com/docs/intro.html', $res['outbound_links']);
        $this->assertContains('https://example.com/docs/tutorials/setup.html', $res['outbound_links']);
        $this->assertContains('https://example.com/api/v1', $res['outbound_links']);
    }

    public function testRelativeUrlNormalization(): void
    {
        $base = 'https://example.com/a/b/c.html';
        $this->assertEquals('https://example.com/a/b/d.html', $this->crawler->resolveRelativeUrl('d.html', $base));
        $this->assertEquals('https://example.com/a/e.html', $this->crawler->resolveRelativeUrl('../e.html', $base));
        $this->assertEquals('https://example.com/root.html', $this->crawler->resolveRelativeUrl('/root.html', $base));
        $this->assertNull($this->crawler->resolveRelativeUrl('#anchor', $base));
        $this->assertNull($this->crawler->resolveRelativeUrl('mailto:admin@example.com', $base));
    }

    public function testTableExtractionToMarkdown(): void
    {
        $html = <<<HTML
<html>
<body>
    <table>
        <tr><th>Feature</th><th>PHP 8.2</th><th>PHP 8.3</th></tr>
        <tr><td>json_validate()</td><td>No</td><td>Yes</td></tr>
        <tr><td>Typed Class Constants</td><td>No</td><td>Yes</td></tr>
    </table>
</body>
</html>
HTML;

        $res = $this->crawler->extractPageContent($html, 'https://example.com/table.html');
        $this->assertCount(1, $res['tables']);
        $this->assertStringContainsString('| Feature | PHP 8.2 | PHP 8.3 |', $res['tables'][0]);
        $this->assertStringContainsString('| json_validate() | No | Yes |', $res['tables'][0]);
    }

    public function testMaxDepthAndPageLimitsEnforcement(): void
    {
        // Mock multi-page crawl using a custom fetch callback
        $mockPages = [
            'https://test.example.com/' => '<html><body><a href="/p1">Page 1</a><a href="/p2">Page 2</a><h1>Home</h1></body></html>',
            'https://test.example.com/p1' => '<html><body><a href="/p1/sub1">Sub 1</a><h1>P1</h1></body></html>',
            'https://test.example.com/p2' => '<html><body><a href="/p2/sub1">Sub 2</a><h1>P2</h1></body></html>',
            'https://test.example.com/p1/sub1' => '<html><body><h1>Sub 1</h1></body></html>',
            'https://test.example.com/p2/sub1' => '<html><body><h1>Sub 2</h1></body></html>',
        ];

        $res = $this->crawler->crawl('https://test.example.com/', [
            'max_depth' => 2,
            'max_pages' => 3,
            'fetch_callback' => function ($url) use ($mockPages) {
                return $mockPages[$url] ?? '<html><body>Empty</body></html>';
            }
        ]);

        $this->assertTrue($res['success']);
        $this->assertLessThanOrEqual(3, $res['total_pages_crawled']);
        $this->assertLessThanOrEqual(2, $res['max_depth_reached']);
        $this->assertNotEmpty($res['link_graph']);
    }

    public function testCrawlDossierOutputStructure(): void
    {
        $mockHtml = '<html><head><title>Docs</title></head><body><h1>Guide</h1><p>Sample documentation.</p></body></html>';
        $res = $this->crawler->crawl('https://docs.example.com/', [
            'max_depth' => 1,
            'max_pages' => 1,
            'fetch_callback' => fn() => $mockHtml
        ]);

        $this->assertTrue($res['success']);
        $this->assertEquals('https://docs.example.com/', $res['seed_url']);
        $this->assertEquals(1, $res['total_pages_crawled']);
        $this->assertGreaterThan(0, $res['total_word_count']);
        $this->assertArrayHasKey('dossier_summary', $res);
        $this->assertArrayHasKey('pages', $res);
    }
}
