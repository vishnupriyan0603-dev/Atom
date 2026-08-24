<?php

namespace Atom\Lsp;

/**
 * LspServer — Master JSON-RPC 2.0 Language Server Protocol Server.
 *
 * Implements the Language Server Protocol specification v3.17:
 * - Lifecycle management (initialize, initialized, shutdown, exit)
 * - JSON-RPC 2.0 request parsing and response framing
 * - Full capability manifest reporting to IDE sidecars
 */
class LspServer
{
    public const JSONRPC_VERSION = '2.0';

    private LspProtocolHandler $handler;
    private bool $initialized = false;
    private bool $shutdown = false;

    public function __construct(?LspProtocolHandler $handler = null)
    {
        $this->handler = $handler ?? new LspProtocolHandler();
    }

    /**
     * Process an incoming JSON-RPC 2.0 request message.
     */
    public function handleRequest(array $request): array
    {
        $id = $request['id'] ?? null;
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];

        // Validate JSON-RPC version
        if (($request['jsonrpc'] ?? '') !== self::JSONRPC_VERSION) {
            return $this->buildErrorResponse($id, -32600, 'Invalid Request: jsonrpc must be "2.0"');
        }

        // 1. Lifecycle Methods
        if ($method === 'initialize') {
            $this->initialized = true;
            return $this->buildSuccessResponse($id, [
                'capabilities' => $this->getServerCapabilities(),
                'serverInfo' => [
                    'name' => 'atom-lsp-server',
                    'version' => '1.0.0-phase26',
                ],
            ]);
        }

        if ($method === 'initialized') {
            return $this->buildSuccessResponse($id, ['status' => 'ready']);
        }

        if ($method === 'shutdown') {
            $this->shutdown = true;
            return $this->buildSuccessResponse($id, null);
        }

        if ($method === 'exit') {
            $this->initialized = false;
            return $this->buildSuccessResponse($id, ['exit_code' => 0]);
        }

        // 2. Dispatch to protocol handler
        $result = $this->handler->dispatch($method, $params);
        if (isset($result['error'])) {
            return $this->buildErrorResponse($id, -32601, $result['error']);
        }

        return $this->buildSuccessResponse($id, $result);
    }

    /**
     * Report server capabilities manifest.
     */
    public function getServerCapabilities(): array
    {
        return [
            'completionProvider' => [
                'resolveProvider' => true,
                'triggerCharacters' => ['.', ':', '>', '$', '\\'],
            ],
            'hoverProvider' => true,
            'codeActionProvider' => true,
            'documentFormattingProvider' => true,
            'diagnosticProvider' => [
                'interFileDependencies' => false,
                'workspaceDiagnostics' => false,
            ],
            'textDocumentSync' => 1, // Full sync
        ];
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function isShutdown(): bool
    {
        return $this->shutdown;
    }

    public function getHandler(): LspProtocolHandler
    {
        return $this->handler;
    }

    private function buildSuccessResponse(?int $id, mixed $result): array
    {
        return [
            'jsonrpc' => self::JSONRPC_VERSION,
            'id' => $id,
            'result' => $result,
        ];
    }

    private function buildErrorResponse(?int $id, int $code, string $message): array
    {
        return [
            'jsonrpc' => self::JSONRPC_VERSION,
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
