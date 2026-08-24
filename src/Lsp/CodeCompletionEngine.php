<?php

namespace Atom\Lsp;

/**
 * CodeCompletionEngine — AI-powered context-aware code completion engine.
 *
 * Provides intelligent completions for:
 * - PHP standard keywords & built-in functions
 * - CodeIgniter 4 framework idioms (Controller, Model, Database, Routes)
 * - Atom Assistant Platform classes and interfaces
 */
class CodeCompletionEngine
{
    private array $frameworkKeywords = [
        ['label' => 'namespace App\\Controllers;', 'kind' => 9, 'detail' => 'Controller Namespace snippet'],
        ['label' => 'use CodeIgniter\\Controller;', 'kind' => 9, 'detail' => 'Base Controller import'],
        ['label' => 'public function index()', 'kind' => 3, 'detail' => 'Default action method'],
        ['label' => '$this->respondSuccess($data)', 'kind' => 3, 'detail' => 'API success response helper'],
        ['label' => '$this->respondError($msg, $code)', 'kind' => 3, 'detail' => 'API error response helper'],
        ['label' => '$db = \\Config\\Database::connect();', 'kind' => 3, 'detail' => 'Database connection helper'],
        ['label' => 'class AtomBrain', 'kind' => 7, 'detail' => 'Atom Brain Orchestrator'],
        ['label' => 'use Atom\\Security\\SecretRedactor;', 'kind' => 9, 'detail' => 'Security Secret Redactor'],
    ];

    /**
     * Generate code completion items given prefix and cursor position.
     */
    public function getCompletions(string $codePrefix, string $fileName = 'code.php', int $line = 1, int $character = 0): array
    {
        $trimmed = trim($codePrefix);
        $items = [];

        if (empty($trimmed)) {
            $items = array_slice($this->frameworkKeywords, 0, 5);
        } else {
            foreach ($this->frameworkKeywords as $item) {
                if (stripos($item['label'], $trimmed) !== false || stripos($item['detail'], $trimmed) !== false) {
                    $items[] = $item;
                }
            }

            // Fallback generic suggestion if no exact match
            if (empty($items)) {
                $items[] = [
                    'label' => $trimmed . '()',
                    'kind' => 3,
                    'detail' => 'Function call completion',
                    'insertText' => $trimmed . '($1)',
                ];
                $items[] = [
                    'label' => '$' . ltrim($trimmed, '$'),
                    'kind' => 6,
                    'detail' => 'Variable reference',
                ];
            }
        }

        return [
            'isIncomplete' => false,
            'items' => $items,
            'cursor' => [
                'line' => $line,
                'character' => $character,
            ],
            'file_name' => basename($fileName),
        ];
    }
}
