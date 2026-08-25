<?php

namespace Atom\Brain;

use Atom\Security\SecretRedactor;

/**
 * SemanticCodeChunkerEngine — Phase 76
 * Autonomous AST-aware semantic code chunk splitter, symbol graph indexer, and call-tree relationship analyzer.
 */
class SemanticCodeChunkerEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Split source code into semantic AST chunks based on class and function boundaries.
     *
     * @param string $sourceCode
     * @param string $language 'php', 'javascript', 'python', 'csharp'
     * @return array Array of semantic code chunks with symbol metadata
     */
    public function splitCodeIntoChunks(string $sourceCode, string $language = 'php'): array
    {
        $cleanCode = $this->redactor->redact($sourceCode);
        if (empty(trim($cleanCode))) {
            return [
                'success' => false,
                'error' => 'Source code cannot be empty',
                'chunks' => [],
                'total_chunks' => 0,
            ];
        }

        $lines = explode("\n", $cleanCode);
        $chunks = [];
        $currentChunk = [];
        $currentSymbol = 'global_scope';
        $currentType = 'module';
        $startLine = 1;

        foreach ($lines as $lineNum => $line) {
            $line1Based = $lineNum + 1;

            // Detect class or interface boundary
            if (preg_match('/\b(class|interface|trait|enum)\s+([a-zA-Z0-9_]+)/i', $line, $matches)) {
                if (!empty($currentChunk) && trim(implode('', $currentChunk)) !== '<?php') {
                    $chunks[] = $this->formatChunk($currentSymbol, $currentType, $currentChunk, $startLine, $lineNum, $language);
                    $currentChunk = [];
                }
                $currentSymbol = $matches[2];
                $currentType = strtolower($matches[1]);
                $startLine = $line1Based;
            }
            // Detect function or method boundary
            elseif (preg_match('/\b(public|protected|private|static)?\s*function\s+([a-zA-Z0-9_]+)\s*\(/i', $line, $matches) ||
                    preg_match('/\b(def|async def)\s+([a-zA-Z0-9_]+)\s*\(/i', $line, $matches)) {
                if (!empty($currentChunk) && count($currentChunk) > 3) {
                    $chunks[] = $this->formatChunk($currentSymbol, $currentType, $currentChunk, $startLine, $lineNum, $language);
                    $currentChunk = [];
                    $startLine = $line1Based;
                }
                $currentSymbol = $matches[2];
                $currentType = 'function';
            }

            $currentChunk[] = $line;
        }

        if (!empty($currentChunk)) {
            $chunks[] = $this->formatChunk($currentSymbol, $currentType, $currentChunk, $startLine, count($lines), $language);
        }

        return [
            'success' => true,
            'language' => strtolower($language),
            'total_lines' => count($lines),
            'total_chunks' => count($chunks),
            'chunks' => $chunks,
        ];
    }

    /**
     * Extract symbol call-tree dependencies and method invocations from code.
     */
    public function extractCallTree(string $sourceCode): array
    {
        $cleanCode = $this->redactor->redact($sourceCode);
        $calls = [];

        // Match method calls: $this->methodName(), $object->methodName(), Class::staticMethod()
        if (preg_match_all('/->([a-zA-Z0-9_]+)\s*\(|::([a-zA-Z0-9_]+)\s*\(/', $cleanCode, $matches)) {
            $rawCalls = array_filter(array_merge($matches[1], $matches[2]));
            foreach ($rawCalls as $c) {
                if (!empty($c) && !in_array($c, $calls, true)) {
                    $calls[] = $c;
                }
            }
        }

        return [
            'success' => true,
            'distinct_calls_found' => count($calls),
            'invoked_symbols' => array_values($calls),
        ];
    }

    private function formatChunk(string $symbol, string $type, array $lines, int $start, int $end, string $language): array
    {
        $content = implode("\n", $lines);
        $tokenEstimate = (int) ceil(strlen($content) / 4);

        return [
            'symbol_name' => $symbol,
            'symbol_type' => $type,
            'start_line' => $start,
            'end_line' => $end,
            'line_count' => count($lines),
            'token_estimate' => $tokenEstimate,
            'content' => $content,
        ];
    }
}
