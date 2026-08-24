<?php

namespace Atom\Desktop;

use Atom\Security\SecretRedactor;

/**
 * ClipboardIntelligence — Proactive clipboard data analyzer and AI action suggester.
 *
 * Automatically classifies clipboard buffer content into:
 * - 'php_code'     — Suggests code review, docblock generation, or syntax linting
 * - 'json'         — Suggests JSON formatting, schema validation
 * - 'sql_query'    — Suggests SQL query explanation or index optimization
 * - 'stack_trace'  — Suggests root-cause error diagnosis
 * - 'url'          — Suggests RAG web ingestion or link inspection
 * - 'plain_text'   — Suggests grammar polish or summarization
 */
class ClipboardIntelligence
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Analyze clipboard text and return classification and action suggestions.
     */
    public function analyzeClipboard(string $content): array
    {
        $trimmed = trim($content);
        if (empty($trimmed)) {
            return [
                'type' => 'empty',
                'length' => 0,
                'summary' => 'Clipboard buffer is empty.',
                'suggested_actions' => [],
                'preview' => '',
            ];
        }

        // Redact secrets in preview
        $cleanPreview = $this->redactor->redact(substr($trimmed, 0, 300));
        $type = $this->detectType($trimmed);
        $actions = $this->getSuggestedActions($type);

        return [
            'type' => $type,
            'length' => strlen($trimmed),
            'summary' => "Detected {$type} buffer (" . strlen($trimmed) . " characters).",
            'suggested_actions' => $actions,
            'preview' => $cleanPreview,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    private function detectType(string $text): string
    {
        // 1. JSON
        if ((str_starts_with($text, '{') && str_ends_with($text, '}')) || (str_starts_with($text, '[') && str_ends_with($text, ']'))) {
            if (json_validate($text)) {
                return 'json';
            }
        }

        // 2. URL
        if (filter_var($text, FILTER_VALIDATE_URL) && (str_starts_with($text, 'http://') || str_starts_with($text, 'https://'))) {
            return 'url';
        }

        // 3. Stack Trace / Error
        if (preg_match('/(Fatal error|Exception|Stack trace|Uncaught|TypeError|Parse error|#\d+\s+)/i', $text)) {
            return 'stack_trace';
        }

        // 4. SQL Query
        if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|TRUNCATE)\s+/i', $text)) {
            return 'sql_query';
        }

        // 5. PHP Code
        if (str_contains($text, '<?php') || preg_match('/(namespace\s+|use\s+App|public\s+function|private\s+function|\$[a-zA-Z0-9_]+\s*=)/', $text)) {
            return 'php_code';
        }

        return 'plain_text';
    }

    private function getSuggestedActions(string $type): array
    {
        return match ($type) {
            'php_code' => [
                ['id' => 'explain_code', 'label' => 'Explain PHP Code', 'icon' => 'code-slash'],
                ['id' => 'add_phpdoc', 'label' => 'Generate PHPDoc Comments', 'icon' => 'chat-left-text'],
                ['id' => 'lint_php', 'label' => 'Lint & Check Syntax', 'icon' => 'shield-check'],
            ],
            'json' => [
                ['id' => 'format_json', 'label' => 'Pretty Print JSON', 'icon' => 'braces'],
                ['id' => 'validate_schema', 'label' => 'Validate JSON Schema', 'icon' => 'check-circle'],
            ],
            'sql_query' => [
                ['id' => 'explain_sql', 'label' => 'Explain SQL Query Plan', 'icon' => 'database'],
                ['id' => 'optimize_query', 'label' => 'Suggest Index Optimizations', 'icon' => 'lightning'],
            ],
            'stack_trace' => [
                ['id' => 'debug_error', 'label' => 'Diagnose Root Cause', 'icon' => 'bug'],
                ['id' => 'suggest_fix', 'label' => 'Generate Code Patch', 'icon' => 'tools'],
            ],
            'url' => [
                ['id' => 'ingest_rag', 'label' => 'Ingest into Knowledge Base', 'icon' => 'cloud-download'],
                ['id' => 'summarize_page', 'label' => 'Summarize Web Content', 'icon' => 'file-earmark-text'],
            ],
            default => [
                ['id' => 'summarize', 'label' => 'Summarize Text', 'icon' => 'text-paragraph'],
                ['id' => 'translate', 'label' => 'Proofread & Polish', 'icon' => 'pencil'],
            ],
        };
    }
}
