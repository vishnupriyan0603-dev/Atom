<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Search\AutonomousWebCrawlerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 103 — Phase103SecurityPassTest security & safety tests (5 tests).
 */
class Phase103SecurityPassTest extends TestCase
{
    private AutonomousWebCrawlerEngine $crawler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crawler = new AutonomousWebCrawlerEngine(new SecretRedactor());
    }

    public function testSsrfDefenseAgainstPrivateAndLoopbackAddresses(): void
    {
        $blockedUrls = [
            'http://127.0.0.1/admin',
            'http://localhost:8080/metrics',
            'http://10.0.0.1/secret',
            'http://192.168.1.1/router',
            'http://172.16.0.5/api',
            'http://169.254.169.254/latest/meta-data/',
            'ftp://example.com/file',
        ];

        foreach ($blockedUrls as $url) {
            $check = $this->crawler->validateSsrfSafety($url);
            $this->assertFalse($check['safe'], "URL '{$url}' should be blocked by SSRF defense");
        }
    }

    public function testSecretRedactionInParsedContentAndUrls(): void
    {
        $html = <<<HTML
<html>
<head><title>Internal Auth sk-proj-1234567890abcdef1234567890</title></head>
<body>
    <p>Use token AIzaSyD1234567890abcdefghijklmnopqrstuvw to authenticate.</p>
    <pre><code>Authorization: Bearer gsk_secrettoken1234567890abcdef</code></pre>
</body>
</html>
HTML;

        $res = $this->crawler->extractPageContent($html, 'https://example.com/auth');
        $this->assertStringNotContainsString('sk-proj-1234567890abcdef1234567890', $res['title']);
        $this->assertStringNotContainsString('AIzaSyD1234567890abcdefghijklmnopqrstuvw', $res['clean_text']);
        $this->assertStringNotContainsString('gsk_secrettoken1234567890abcdef', $res['code_blocks'][0]);
    }

    public function testCircularRedirectAndInfiniteLoopDefense(): void
    {
        $mockPages = [
            'https://loop.example.com/'  => '<html><body><a href="/step1">Step 1</a></body></html>',
            'https://loop.example.com/step1' => '<html><body><a href="/step2">Step 2</a></body></html>',
            'https://loop.example.com/step2' => '<html><body><a href="/">Back to Root</a></body></html>',
        ];

        $res = $this->crawler->crawl('https://loop.example.com/', [
            'max_depth' => 3,
            'max_pages' => 10,
            'fetch_callback' => function ($url) use ($mockPages) {
                return $mockPages[$url] ?? '<html><body>Empty</body></html>';
            }
        ]);

        $this->assertTrue($res['success']);
        // Even with circular links, each unique URL is visited at most once
        $this->assertCount(3, $res['pages']);
    }

    public function testMalformedHtmlAndXssInjectionPayloadResilience(): void
    {
        $malformedHtml = '<title><script>alert("XSS")</script>Unclosed title<h1>Heading with <img src=x onerror=alert(1)></h1><p>Body text<script>document.cookie="stolen";</script></p>';

        $res = $this->crawler->extractPageContent($malformedHtml, 'https://example.com/xss');
        $this->assertStringNotContainsString('<script>', $res['clean_text']);
        $this->assertStringNotContainsString('document.cookie', $res['clean_text']);
        $this->assertNotEmpty($res['clean_text']);
    }

    public function testLargeContentTruncationAndMemoryProtection(): void
    {
        // Generate a 200KB large text payload
        $hugeParagraph = str_repeat('PHP 8.3 high performance asynchronous scaling. ', 5000);
        $hugeHtml = '<html><head><title>Huge Page</title></head><body><p>' . $hugeParagraph . '</p></body></html>';

        $res = $this->crawler->extractPageContent($hugeHtml, 'https://example.com/huge');
        $this->assertLessThanOrEqual(70000, strlen($res['clean_text']));
        $this->assertStringContainsString('truncated for memory safety', $res['clean_text']);
    }
}
