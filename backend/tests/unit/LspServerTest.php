<?php

use PHPUnit\Framework\TestCase;
use Atom\Lsp\LspServer;

/**
 * Phase 26 — LspServer unit tests (5 tests).
 */
class LspServerTest extends TestCase
{
    private LspServer $server;

    protected function setUp(): void
    {
        $this->server = new LspServer();
    }

    public function testInitializeMethodReturnsCapabilities(): void
    {
        $req = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => ['processId' => 1234],
        ];

        $res = $this->server->handleRequest($req);
        $this->assertSame('2.0', $res['jsonrpc']);
        $this->assertSame(1, $res['id']);
        $this->assertTrue($this->server->isInitialized());
        $this->assertArrayHasKey('capabilities', $res['result']);
        $this->assertTrue($res['result']['capabilities']['hoverProvider']);
    }

    public function testShutdownAndExitLifecycle(): void
    {
        $shutdownReq = [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'shutdown',
        ];
        $res = $this->server->handleRequest($shutdownReq);
        $this->assertTrue($this->server->isShutdown());
        $this->assertNull($res['result']);

        $exitReq = [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'exit',
        ];
        $exitRes = $this->server->handleRequest($exitReq);
        $this->assertSame(0, $exitRes['result']['exit_code']);
    }

    public function testRejectsInvalidJsonRpcVersion(): void
    {
        $invalidReq = [
            'jsonrpc' => '1.0',
            'id' => 4,
            'method' => 'initialize',
        ];
        $res = $this->server->handleRequest($invalidReq);
        $this->assertArrayHasKey('error', $res);
        $this->assertSame(-32600, $res['error']['code']);
    }

    public function testServerCapabilitiesManifest(): void
    {
        $caps = $this->server->getServerCapabilities();
        $this->assertArrayHasKey('completionProvider', $caps);
        $this->assertArrayHasKey('hoverProvider', $caps);
        $this->assertArrayHasKey('codeActionProvider', $caps);
        $this->assertArrayHasKey('documentFormattingProvider', $caps);
    }

    public function testUnknownMethodReturnsMethodNotFound(): void
    {
        $req = [
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'custom/unknownMethod',
            'params' => [],
        ];
        $res = $this->server->handleRequest($req);
        $this->assertArrayHasKey('error', $res);
        $this->assertSame(-32601, $res['error']['code']);
    }
}
