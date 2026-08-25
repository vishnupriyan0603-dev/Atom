<?php

namespace Atom\Logging;

use Atom\Security\SecretRedactor;
use Atom\Testing\SelfCorrectionEngine;

/**
 * RuntimeErrorLogger — Centralized runtime error logger with automated root cause diagnosis and fix synthesis.
 */
class RuntimeErrorLogger
{
    private static ?RuntimeErrorLogger $instance = null;
    private SecretRedactor $redactor;
    private SelfCorrectionEngine $correctionEngine;
    private string $logFilePath;
    /** @var array<int, array> */
    private array $memoryBuffer = [];
    private const MAX_BUFFER_SIZE = 200;

    public function __construct(?string $logFilePath = null, ?SecretRedactor $redactor = null, ?SelfCorrectionEngine $correctionEngine = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->correctionEngine = $correctionEngine ?? new SelfCorrectionEngine($this->redactor);
        
        if ($logFilePath === null) {
            $baseDir = dirname(__DIR__, 2);
            $logDir = $baseDir . DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            $this->logFilePath = $logDir . DIRECTORY_SEPARATOR . 'atom_runtime_errors.json';
        } else {
            $this->logFilePath = $logFilePath;
        }

        $this->loadFromFile();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Record a runtime error with automatic diagnosis and fix recommendation.
     *
     * @param array $data Expected fields: message, file, line, stack_trace, source, user_action, context, level
     * @return array The recorded error entry
     */
    public function logError(array $data): array
    {
        $id = 'err_' . bin2hex(random_bytes(6)) . '_' . time();
        $timestamp = date('c');
        $rawMessage = (string)($data['message'] ?? 'Unknown runtime error');
        $rawFile = (string)($data['file'] ?? 'unknown');
        $line = (int)($data['line'] ?? 0);
        $source = (string)($data['source'] ?? 'client'); // client | server | api | cli
        $level = strtolower((string)($data['level'] ?? 'error'));
        $userAction = (string)($data['user_action'] ?? 'General operation');
        $stackTrace = (string)($data['stack_trace'] ?? '');
        $context = is_array($data['context'] ?? null) ? $data['context'] : [];

        // Redact secrets and sensitive inputs
        $cleanMessage = $this->redactor->redact($rawMessage);
        $cleanStackTrace = $this->redactor->redact($stackTrace);
        $cleanContext = $this->redactor->redact(json_encode($context));
        $parsedContext = json_decode($cleanContext, true) ?: [];

        // Diagnose root cause
        $diagString = "{$cleanMessage} in {$rawFile}:{$line}\n{$cleanStackTrace}";
        $diagnosis = $this->correctionEngine->diagnoseFailure($diagString);

        // Generate intelligent fix suggestions
        $fixSuggestion = $this->generateFixSuggestion($cleanMessage, $rawFile, $line, $diagnosis, $source);

        $entry = [
            'id' => $id,
            'timestamp' => $timestamp,
            'source' => $source,
            'level' => $level,
            'message' => $cleanMessage,
            'file' => $rawFile,
            'line' => $line,
            'stack_trace' => $cleanStackTrace,
            'user_action' => $userAction,
            'context' => $parsedContext,
            'diagnosis' => $diagnosis,
            'fix_suggestion' => $fixSuggestion,
            'status' => 'unresolved', // unresolved | resolved | auto_fixed
            'resolved_at' => null,
            'resolution_notes' => null,
        ];

        array_unshift($this->memoryBuffer, $entry);
        if (count($this->memoryBuffer) > self::MAX_BUFFER_SIZE) {
            $this->memoryBuffer = array_slice($this->memoryBuffer, 0, self::MAX_BUFFER_SIZE);
        }

        $this->saveToFile();
        return $entry;
    }

    /**
     * Get logged errors with optional status filtering.
     */
    public function getErrors(?string $status = null, int $limit = 50): array
    {
        $results = $this->memoryBuffer;
        if ($status !== null && $status !== '' && $status !== 'all') {
            $results = array_values(array_filter($results, fn($e) => ($e['status'] ?? '') === $status));
        }
        return array_slice($results, 0, $limit);
    }

    /**
     * Get single error by ID.
     */
    public function getErrorById(string $id): ?array
    {
        foreach ($this->memoryBuffer as $entry) {
            if ($entry['id'] === $id) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Mark an error as resolved.
     */
    public function resolveError(string $id, string $resolutionNotes = 'Resolved by operator'): bool
    {
        $found = false;
        foreach ($this->memoryBuffer as &$entry) {
            if ($entry['id'] === $id) {
                $entry['status'] = 'resolved';
                $entry['resolved_at'] = date('c');
                $entry['resolution_notes'] = $resolutionNotes;
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->saveToFile();
        }
        return $found;
    }

    /**
     * Synthesize automated code fix / patch for a logged error.
     */
    public function autoFix(string $id, ?string $fileContent = null): array
    {
        $error = $this->getErrorById($id);
        if (!$error) {
            return ['success' => false, 'error' => 'Error record not found'];
        }

        $codeToPatch = $fileContent ?? "// Faulty location: {$error['file']}:{$error['line']}\n// Issue: {$error['message']}\n";
        $patchResult = $this->correctionEngine->synthesizePatch($codeToPatch, $error['message']);

        // Update error status to auto_fixed
        $this->resolveError($id, 'Auto-fix patch generated: ' . $patchResult['explanation']);

        return [
            'success' => true,
            'error_id' => $id,
            'diagnosis' => $error['diagnosis'],
            'fix_suggestion' => $error['fix_suggestion'],
            'patch' => $patchResult,
        ];
    }

    /**
     * Clear all logged errors.
     */
    public function clearErrors(): bool
    {
        $this->memoryBuffer = [];
        $this->saveToFile();
        return true;
    }

    /**
     * Generate contextual recommendations based on error signature.
     */
    private function generateFixSuggestion(string $message, string $file, int $line, array $diagnosis, string $source): array
    {
        $category = 'General Fix';
        $steps = [];
        $codeSnippet = '';

        if (stripos($message, 'not valid JSON') !== false || stripos($message, 'Unexpected token') !== false) {
            $category = 'API / JSON Response Handling';
            $steps = [
                'Verify the API endpoint is returning JSON headers (Content-Type: application/json).',
                'Use safeJsonFetch() or safeParseResponse() to catch HTML error documents before JSON.parse().',
                'Ensure API base URL points to the active server origin.',
            ];
            $codeSnippet = "const data = await safeJsonFetch('/api/endpoint');\nif (!data.success) { /* handle error */ }";
        } elseif (stripos($message, 'Failed to fetch') !== false || stripos($message, 'NetworkError') !== false || stripos($message, '404') !== false) {
            $category = 'Network & Connectivity';
            $steps = [
                'Ensure backend server is running (e.g. php spark serve on port 8080 or Apache port 80).',
                'Verify CORS headers in backend filters.',
                'Check that the route is registered in backend/app/Config/Routes.php.',
            ];
            $codeSnippet = "const API_BASE = window.ATOM_API_BASE || 'http://localhost:8080/api';";
        } elseif (stripos($message, 'Cannot read property') !== false || stripos($message, 'undefined') !== false || stripos($message, 'null') !== false) {
            $category = 'Null / Undefined Reference Safety';
            $steps = [
                'Add optional chaining (?.) when traversing nested objects.',
                'Ensure DOM elements exist before attaching listeners or reading values.',
                'Provide fallback default values for missing keys.',
            ];
            $codeSnippet = "const value = data?.items?.[0]?.name ?? 'Default';";
        } elseif (stripos($message, 'SQLSTATE') !== false || stripos($message, 'Database') !== false) {
            $category = 'Database Query & Connection';
            $steps = [
                'Check database credentials and table schema in backend/app/Config/Database.php.',
                'Run migrations or ensure SQLite database file is writable.',
                'Use prepared statements or Query Builder to avoid syntax and injection issues.',
            ];
        } else {
            $category = 'Application Logic';
            $steps = [
                "Inspect {$file}" . ($line > 0 ? " at line {$line}" : '') . ' for edge cases.',
                'Verify input arguments and function preconditions.',
                'Check backend logs in writable/logs/ for full exception stack traces.',
            ];
        }

        return [
            'category' => $category,
            'steps' => $steps,
            'suggested_code' => $codeSnippet,
            'summary' => "Action required for '{$diagnosis['error_type']}': " . implode(' ', $steps),
        ];
    }

    private function loadFromFile(): void
    {
        if (file_exists($this->logFilePath)) {
            $content = @file_get_contents($this->logFilePath);
            if (!empty($content)) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $this->memoryBuffer = $decoded;
                }
            }
        }
    }

    private function saveToFile(): void
    {
        $dir = dirname($this->logFilePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @file_put_contents($this->logFilePath, json_encode($this->memoryBuffer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
