<?php

namespace App\Controllers\Api;

use Atom\Lsp\LspServer;
use Atom\Lsp\CodeCompletionEngine;
use Atom\Lsp\AstRefactoringEngine;

/**
 * LSP API Controller — Phase 26
 *
 * Endpoints:
 * - POST /api/v1/lsp/rpc          — Standard JSON-RPC 2.0 endpoint
 * - POST /api/v1/lsp/complete     — Code autocompletion endpoint
 * - POST /api/v1/lsp/hover        — Hover explanation endpoint
 * - POST /api/v1/lsp/refactor     — AST refactoring endpoint
 * - GET  /api/v1/lsp/capabilities — LSP server capability manifest
 */
class Lsp extends BaseApiController
{
    private static ?LspServer $serverInstance = null;

    private function getServer(): LspServer
    {
        if (self::$serverInstance === null) {
            self::$serverInstance = new LspServer();
        }
        return self::$serverInstance;
    }

    /**
     * POST /api/v1/lsp/rpc
     */
    public function rpc()
    {
        $json = $this->request->getJSON(true) ?? [];
        $server = $this->getServer();
        $response = $server->handleRequest($json);

        return $this->respond($response);
    }

    /**
     * POST /api/v1/lsp/complete
     */
    public function complete()
    {
        $json = $this->request->getJSON(true) ?? [];
        $prefix = $json['prefix'] ?? '';
        $fileName = $json['file_name'] ?? 'code.php';
        $line = (int) ($json['line'] ?? 1);
        $char = (int) ($json['character'] ?? 0);

        $engine = new CodeCompletionEngine();
        $result = $engine->getCompletions($prefix, $fileName, $line, $char);

        return $this->respondSuccess($result, 'Completions retrieved');
    }

    /**
     * POST /api/v1/lsp/hover
     */
    public function hover()
    {
        $json = $this->request->getJSON(true) ?? [];
        $symbol = $json['symbol'] ?? 'AtomBrain';

        $server = $this->getServer();
        $result = $server->getHandler()->dispatch('textDocument/hover', ['symbol' => $symbol]);

        return $this->respondSuccess($result, 'Hover information retrieved');
    }

    /**
     * POST /api/v1/lsp/refactor
     */
    public function refactor()
    {
        $json = $this->request->getJSON(true) ?? [];
        $code = $json['code'] ?? '';
        $action = $json['action'] ?? 'format_syntax';
        $options = $json['options'] ?? [];

        if (empty($code)) {
            return $this->respondError('Missing code parameter', 400);
        }

        $engine = new AstRefactoringEngine();
        $result = $engine->refactor($code, $action, $options);

        return $this->respondSuccess($result, 'Code refactored successfully');
    }

    /**
     * GET /api/v1/lsp/capabilities
     */
    public function capabilities()
    {
        $server = $this->getServer();
        return $this->respondSuccess([
            'jsonrpc' => '2.0',
            'server' => 'atom-lsp-server',
            'capabilities' => $server->getServerCapabilities(),
        ], 'Capabilities retrieved');
    }
}
