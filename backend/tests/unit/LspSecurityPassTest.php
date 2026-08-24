<?php

use PHPUnit\Framework\TestCase;
use Atom\Lsp\LspServer;
use Atom\Lsp\AstRefactoringEngine;

/**
 * Phase 26 — LspSecurityPassTest (5 tests).
 *
 * Enforces safety boundaries for Language Server Protocol operations:
 * - Secret redaction in hover explanations
 * - Secret redaction in refactored code outputs
 * - Strict JSON-RPC 2.0 protocol validation
 * - Safe handling of malformed parameters
 * - Safe fallback for unknown symbols
 */
class LspSecurityPassTest extends TestCase
{
    private LspServer $server;

    protected function setUp(): void
    {
        $this->server = new LspServer();
    }

    public function testSecretRedactionInRefactoredCode(): void
    {
        $engine = new AstRefactoringEngine();
        $code = "\$apiKey = 'sk-1234567890abcdef1234567890abcdef';\npublic function connect() {}";
        $res = $engine->refactor($code, 'add_phpdoc');

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', $res['transformed_code']);
    }

    public function testSecretRedactionInHoverOutput(): void
    {
        $res = $this->server->getHandler()->dispatch('textDocument/hover', ['symbol' => 'sk-1234567890abcdef1234567890abcdef']);
        $this->assertArrayHasKey('contents', $res);
        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', $res['contents']['value']);
    }

    public function testRejectsEmptyJsonRpcPayload(): void
    {
        $res = $this->server->handleRequest([]);
        $this->assertArrayHasKey('error', $res);
        $this->assertSame(-32600, $res['error']['code']);
    }

    public function testHandlesNullIdInErrorGracefully(): void
    {
        $res = $this->server->handleRequest(['method' => 'test']);
        $this->assertArrayHasKey('error', $res);
        $this->assertNull($res['id']);
    }

    public function testServerCapabilitiesDoNotLeakWorkspacePaths(): void
    {
        $caps = $this->server->getServerCapabilities();
        $json = json_encode($caps);
        $this->assertStringNotContainsString('C:\\', $json);
        $this->assertStringNotContainsString('/var/www', $json);
    }
}
