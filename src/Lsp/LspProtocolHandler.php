<?php

namespace Atom\Lsp;

use Atom\Security\SecretRedactor;

/**
 * LspProtocolHandler — Dispatches standard Language Server Protocol requests.
 */
class LspProtocolHandler
{
    private CodeCompletionEngine $completionEngine;
    private AstRefactoringEngine $refactoringEngine;
    private SecretRedactor $redactor;

    public function __construct(
        ?CodeCompletionEngine $completionEngine = null,
        ?AstRefactoringEngine $refactoringEngine = null,
        ?SecretRedactor $redactor = null
    ) {
        $this->completionEngine = $completionEngine ?? new CodeCompletionEngine();
        $this->refactoringEngine = $refactoringEngine ?? new AstRefactoringEngine();
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Dispatch an LSP method call with incoming parameters.
     */
    public function dispatch(string $method, array $params): array
    {
        return match ($method) {
            'textDocument/completion' => $this->handleCompletion($params),
            'textDocument/hover' => $this->handleHover($params),
            'textDocument/codeAction' => $this->handleCodeAction($params),
            'textDocument/formatting' => $this->handleFormatting($params),
            'textDocument/diagnostic' => $this->handleDiagnostic($params),
            default => [
                'error' => "Unhandled LSP method: {$method}",
            ],
        };
    }

    private function handleCompletion(array $params): array
    {
        $uri = $params['textDocument']['uri'] ?? 'file:///code.php';
        $pos = $params['position'] ?? ['line' => 0, 'character' => 0];
        $prefix = $params['context']['triggerCharacter'] ?? ($params['prefix'] ?? '');

        return $this->completionEngine->getCompletions(
            $prefix,
            $uri,
            $pos['line'] ?? 0,
            $pos['character'] ?? 0
        );
    }

    private function handleHover(array $params): array
    {
        $symbol = $params['symbol'] ?? ($params['word'] ?? 'AtomBrain');
        $explanation = "### Symbol: `{$symbol}`\n"
            . "**Platform Context**: Atom AI Assistant Engine\n"
            . "**Documentation**: Provides central coordination for multi-modal tools, memories, and model routing.\n"
            . "```php\n"
            . "public function process(string \$input, array &\$history): string\n"
            . "```";

        $explanation = $this->redactor->redact($explanation);

        return [
            'contents' => [
                'kind' => 'markdown',
                'value' => $explanation,
            ],
        ];
    }

    private function handleCodeAction(array $params): array
    {
        $code = $params['code'] ?? '';
        return [
            'actions' => [
                ['title' => 'Generate PHPDoc Comments', 'action' => 'add_phpdoc'],
                ['title' => 'Inject Missing Type Hints', 'action' => 'add_type_hints'],
                ['title' => 'Extract Helper Method', 'action' => 'extract_method'],
                ['title' => 'Format PSR-12 Syntax', 'action' => 'format_syntax'],
            ],
        ];
    }

    private function handleFormatting(array $params): array
    {
        $code = $params['code'] ?? '';
        return $this->refactoringEngine->refactor($code, 'format_syntax');
    }

    private function handleDiagnostic(array $params): array
    {
        $code = $params['code'] ?? '';
        $diagnostics = [];

        // Check for missing return types
        if (preg_match('/function\s+[a-zA-Z0-9_]+\s*\([^)]*\)\s*\{/i', $code)) {
            $diagnostics[] = [
                'range' => ['start' => ['line' => 1, 'character' => 0], 'end' => ['line' => 1, 'character' => 10]],
                'severity' => 2, // Warning
                'message' => 'Function is missing explicit return type declaration.',
                'source' => 'atom-lsp-diagnostic',
            ];
        }

        return [
            'diagnostics' => $diagnostics,
            'issues_count' => count($diagnostics),
        ];
    }

    public function getCompletionEngine(): CodeCompletionEngine
    {
        return $this->completionEngine;
    }

    public function getRefactoringEngine(): AstRefactoringEngine
    {
        return $this->refactoringEngine;
    }
}
