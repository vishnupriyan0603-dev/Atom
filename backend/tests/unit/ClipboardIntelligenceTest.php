<?php

use PHPUnit\Framework\TestCase;
use Atom\Desktop\ClipboardIntelligence;

/**
 * Phase 27 — ClipboardIntelligence unit tests (5 tests).
 */
class ClipboardIntelligenceTest extends TestCase
{
    private ClipboardIntelligence $clipboard;

    protected function setUp(): void
    {
        $this->clipboard = new ClipboardIntelligence();
    }

    public function testDetectPhpCodeSnippet(): void
    {
        $code = "<?php\nnamespace App;\npublic function index() { return 1; }";
        $res = $this->clipboard->analyzeClipboard($code);

        $this->assertSame('php_code', $res['type']);
        $this->assertNotEmpty($res['suggested_actions']);
    }

    public function testDetectJsonPayload(): void
    {
        $json = '{"status":"active","count":10,"tags":["php","ai"]}';
        $res = $this->clipboard->analyzeClipboard($json);

        $this->assertSame('json', $res['type']);
        $this->assertStringContainsString('Pretty Print JSON', $res['suggested_actions'][0]['label']);
    }

    public function testDetectSqlQuery(): void
    {
        $sql = "SELECT id, username FROM users WHERE role = 'admin' LIMIT 10;";
        $res = $this->clipboard->analyzeClipboard($sql);

        $this->assertSame('sql_query', $res['type']);
        $this->assertStringContainsString('SQL', $res['suggested_actions'][0]['label']);
    }

    public function testDetectStackTrace(): void
    {
        $trace = "Fatal error: Uncaught Exception: Database connection refused in /var/www/DB.php:45";
        $res = $this->clipboard->analyzeClipboard($trace);

        $this->assertSame('stack_trace', $res['type']);
        $this->assertSame('debug_error', $res['suggested_actions'][0]['id']);
    }

    public function testEmptyClipboardHandling(): void
    {
        $res = $this->clipboard->analyzeClipboard('   ');
        $this->assertSame('empty', $res['type']);
        $this->assertSame(0, $res['length']);
        $this->assertEmpty($res['suggested_actions']);
    }
}
