<?php

use PHPUnit\Framework\TestCase;
use Atom\Lsp\LspProtocolHandler;

/**
 * Phase 26 — LspProtocolHandler unit tests (5 tests).
 */
class LspProtocolHandlerTest extends TestCase
{
    private LspProtocolHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new LspProtocolHandler();
    }

    public function testCompletionDispatchReturnsItems(): void
    {
        $params = [
            'textDocument' => ['uri' => 'file:///app/Controllers/TestController.php'],
            'position' => ['line' => 5, 'character' => 10],
            'prefix' => 'public function',
        ];

        $res = $this->handler->dispatch('textDocument/completion', $params);
        $this->assertArrayHasKey('items', $res);
        $this->assertNotEmpty($res['items']);
    }

    public function testHoverDispatchReturnsMarkdownContent(): void
    {
        $params = ['symbol' => 'AtomBrain'];
        $res = $this->handler->dispatch('textDocument/hover', $params);

        $this->assertArrayHasKey('contents', $res);
        $this->assertSame('markdown', $res['contents']['kind']);
        $this->assertStringContainsString('AtomBrain', $res['contents']['value']);
    }

    public function testCodeActionDispatchReturnsRefactoringOptions(): void
    {
        $params = ['code' => 'function foo() {}'];
        $res = $this->handler->dispatch('textDocument/codeAction', $params);

        $this->assertArrayHasKey('actions', $res);
        $this->assertGreaterThanOrEqual(3, count($res['actions']));
    }

    public function testFormattingDispatchFormatsSyntax(): void
    {
        $params = ['code' => "  \$x = 10;   \n"];
        $res = $this->handler->dispatch('textDocument/formatting', $params);

        $this->assertTrue($res['success']);
        $this->assertSame('format_syntax', $res['action']);
        $this->assertNotEmpty($res['transformed_code']);
    }

    public function testDiagnosticDispatchDetectsIssues(): void
    {
        $params = ['code' => 'function calculateTotal($val) { return $val * 2; }'];
        $res = $this->handler->dispatch('textDocument/diagnostic', $params);

        $this->assertArrayHasKey('diagnostics', $res);
        $this->assertArrayHasKey('issues_count', $res);
    }
}
