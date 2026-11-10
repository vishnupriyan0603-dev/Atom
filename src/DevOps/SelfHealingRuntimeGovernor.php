<?php

namespace Atom\DevOps;

class SelfHealingRuntimeGovernor
{
    private string $workspaceRoot;
    private array $healingLog = [];
    private array $patchBackups = [];

    public function __construct(?string $workspaceRoot = null)
    {
        $this->workspaceRoot = $workspaceRoot ?? str_replace('\\', '/', getcwd());
    }

    /**
     * Analyze an error message or exception and attempt automated self-healing.
     */
    public function diagnoseAndHeal(string $errorMessage, ?string $file = null, ?int $line = null): array
    {
        $targetFile = $file;
        $targetLine = $line;

        // Parse file and line from error message if not provided
        if ($targetFile === null && preg_match('/(?:in|at)\s+([a-zA-Z0-9_\-\.\/\\\:]+\.php)(?:\s+on\s+line|\:)?\s*(\d+)?/i', $errorMessage, $m)) {
            $targetFile = str_replace('\\', '/', $m[1]);
            $targetLine = isset($m[2]) ? (int)$m[2] : null;
        }

        if (empty($targetFile) || !file_exists($targetFile)) {
            return [
                'success' => false,
                'action' => 'diagnose_and_heal',
                'error' => 'Could not determine valid target file from error message.',
                'raw_error' => $errorMessage,
            ];
        }

        $content = file_get_contents($targetFile);
        $originalHash = md5($content);

        // Pattern A: Missing closing brace or unexpected syntax error
        if (str_contains($errorMessage, 'unexpected token "public"') || str_contains($errorMessage, 'unexpected token "function"')) {
            $fixed = $this->fixUnclosedBlocks($content, $targetLine);
            if ($fixed !== null && $fixed !== $content) {
                return $this->applyAndVerifyPatch($targetFile, $fixed, $originalHash, 'Unclosed block syntax healing');
            }
        }

        // Pattern B: Undefined array key / null pointer
        if (str_contains(strtolower($errorMessage), 'undefined array key') || str_contains(strtolower($errorMessage), 'undefined index')) {
            $fixed = $this->fixUndefinedArrayKey($content, $errorMessage, $targetLine);
            if ($fixed !== null && $fixed !== $content) {
                return $this->applyAndVerifyPatch($targetFile, $fixed, $originalHash, 'Null coalescing array key healing');
            }
        }

        // Pattern C: Call to undefined method (missing public method stub or alias)
        if (preg_match('/Call to undefined method\s+([^\s:]+)::([a-zA-Z0-9_]+)/i', $errorMessage, $m)) {
            $method = $m[2] ?? '';
            if (!empty($method)) {
                $fixed = $this->stubMissingMethod($content, $method);
                if ($fixed !== null && $fixed !== $content) {
                    return $this->applyAndVerifyPatch($targetFile, $fixed, $originalHash, "Generated method stub for {$method}()");
                }
            }
        }

        return [
            'success' => false,
            'action' => 'diagnose_and_heal',
            'file' => $targetFile,
            'line' => $targetLine,
            'message' => 'No deterministic AST healing pattern matched. Requires manual developer review.',
        ];
    }

    private function fixUnclosedBlocks(string $content, ?int $line): ?string
    {
        $lines = explode("\n", $content);
        if ($line === null || $line < 1 || $line > count($lines)) {
            return null;
        }

        $insertIdx = $line - 1;
        array_splice($lines, $insertIdx, 0, ["    }\n"]);
        return implode("\n", $lines);
    }

    private function fixUndefinedArrayKey(string $content, string $error, ?int $line): ?string
    {
        if ($line === null) {
            return null;
        }
        $lines = explode("\n", $content);
        if ($line > count($lines)) {
            return null;
        }

        $targetIdx = $line - 1;
        $targetLineContent = $lines[$targetIdx];

        $patchedLine = preg_replace('/(\$[a-zA-Z0-9_]+\[[\'"][a-zA-Z0-9_\-]+[\'"]\])(?!\s*\?\?)/', '($1 ?? null)', $targetLineContent);

        if ($patchedLine !== $targetLineContent) {
            $lines[$targetIdx] = $patchedLine;
            return implode("\n", $lines);
        }

        return null;
    }

    private function stubMissingMethod(string $content, string $methodName): ?string
    {
        $stub = "\n    public function {$methodName}(mixed ...\$args): mixed\n    {\n        return null;\n    }\n";
        $pos = strrpos($content, '}');
        if ($pos === false) {
            return null;
        }

        return substr($content, 0, $pos) . $stub . substr($content, $pos);
    }

    private function applyAndVerifyPatch(string $file, string $newContent, string $originalHash, string $description): array
    {
        $backup = file_get_contents($file);
        $this->patchBackups[$file] = $backup;

        file_put_contents($file, $newContent);

        $tmpFile = tempnam(sys_get_temp_dir(), 'atom_heal_');
        file_put_contents($tmpFile, $newContent);
        
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $pipes = [];
        $process = proc_open([PHP_BINARY, '-l', $tmpFile], $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        $lintOutput = '';
        $exitCode = 1;
        if (is_resource($process)) {
            $lintOutput .= stream_get_contents($pipes[1]) ?: '';
            $lintOutput .= stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
        }
        @unlink($tmpFile);

        if ($exitCode !== 0) {
            file_put_contents($file, $backup);
            return [
                'success' => false,
                'action' => 'apply_patch',
                'file' => $file,
                'error' => 'Generated patch failed PHP syntax verification. Auto-rollback executed.',
                'lint_output' => trim($lintOutput),
            ];
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => $file,
            'description' => $description,
            'original_hash' => $originalHash,
            'new_hash' => md5($newContent),
            'verified' => true,
        ];
        $this->healingLog[] = $logEntry;

        return [
            'success' => true,
            'action' => 'apply_patch',
            'file' => $file,
            'description' => $description,
            'verified' => true,
            'message' => "Successfully healed {$file}: {$description}",
        ];
    }

    public function rollback(string $file): bool
    {
        if (isset($this->patchBackups[$file])) {
            file_put_contents($file, $this->patchBackups[$file]);
            unset($this->patchBackups[$file]);
            return true;
        }
        return false;
    }

    public function getHealingLog(): array
    {
        return $this->healingLog;
    }
}
