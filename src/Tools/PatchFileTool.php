<?php

namespace Atom\Tools;

use Atom\Security\WorkspaceGuard;
use Atom\Security\FilePolicy;

class PatchFileTool implements ToolInterface
{
    private WorkspaceGuard $guard;
    private FilePolicy $policy;
    private PhpLintTool $linter;
    private string $backupDir;

    public function __construct(
        WorkspaceGuard $guard,
        FilePolicy $policy,
        PhpLintTool $linter,
        string $workspaceRoot
    ) {
        $this->guard = $guard;
        $this->policy = $policy;
        $this->linter = $linter;
        $this->backupDir = rtrim(str_replace('\\', '/', $workspaceRoot), '/') . '/storage/backups';
    }

    public function getName(): string
    {
        return 'patch_file';
    }

    public function execute(array $input): array
    {
        $filePath = $input['file_path'] ?? '';
        $targetContent = $input['target_content'] ?? '';
        $replacementContent = $input['replacement_content'] ?? '';
        $interactive = $input['interactive'] ?? true;

        if (empty($filePath) || empty($targetContent)) {
            return [
                'success' => false,
                'error' => 'Missing parameter: file_path or target_content'
            ];
        }

        try {
            $safePath = $this->guard->getSafePath($filePath);

            // Validate read/write allowance
            if (!$this->policy->isAllowed($safePath)) {
                return [
                    'success' => false,
                    'error' => 'Security Policy: Write restricted for this file extension: ' . basename($safePath)
                ];
            }

            if (!is_file($safePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found: ' . $filePath
                ];
            }

            $currentContent = file_get_contents($safePath);
            if ($currentContent === false) {
                return [
                    'success' => false,
                    'error' => 'Unable to read file content: ' . $filePath
                ];
            }

            // Find exact match
            if (strpos($currentContent, $targetContent) === false) {
                return [
                    'success' => false,
                    'error' => 'Target content match not found inside file: ' . $filePath
                ];
            }

            // Create backup directory if missing
            if (!is_dir($this->backupDir)) {
                mkdir($this->backupDir, 0755, true);
            }

            // Write backup file
            $filename = basename($safePath);
            $backupPath = $this->backupDir . '/' . $filename . '.' . time() . '.atom-backup';
            if (file_put_contents($backupPath, $currentContent) === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to create pre-write backup copy.'
                ];
            }

            // Perform replacement
            $newContent = str_replace($targetContent, $replacementContent, $currentContent);

            // Generate dry run diff display
            $diffOutput = $this->generateDiff($filename, $targetContent, $replacementContent);

            // If interactive, prompt for validation
            if ($interactive) {
                echo PHP_EOL . "\033[36m--- Proposed Patch Diff (" . $filename . ") ---\033[0m" . PHP_EOL;
                echo $diffOutput;
                echo "\033[36m---------------------------------------\033[0m" . PHP_EOL;
                echo "\033[1mApply this patch? (Y/N): \033[0m";
                $ans = trim(fgets(STDIN));
                if (strtolower($ans) !== 'y') {
                    unlink($backupPath); // Delete backup if cancelled
                    return [
                        'success' => false,
                        'error' => 'Patch application cancelled by user.'
                    ];
                }
            }

            // Write new content to file
            if (file_put_contents($safePath, $newContent) === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to write patch modifications to file.'
                ];
            }

            // Syntax lint validation
            if (pathinfo($safePath, PATHINFO_EXTENSION) === 'php') {
                $res = $this->linter->execute(['file_path' => $safePath]);
                if (!$res['success']) {
                    // Rollback immediately
                    file_put_contents($safePath, $currentContent);
                    unlink($backupPath);
                    return [
                        'success' => false,
                        'error' => 'PHP Syntax Lint Error. Applied Rollback to backup state: ' . ($res['error'] ?? $res['output'])
                    ];
                }
            }

            return [
                'success' => true,
                'output' => 'Patch applied successfully. Pre-write backup stored: ' . basename($backupPath)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function generateDiff(string $filename, string $target, string $replacement): string
    {
        $output = '';
        $targetLines = explode("\n", $target);
        $replacementLines = explode("\n", $replacement);

        foreach ($targetLines as $line) {
            $output .= "\033[31m- " . rtrim($line) . "\033[0m" . PHP_EOL;
        }
        foreach ($replacementLines as $line) {
            $output .= "\033[32m+ " . rtrim($line) . "\033[0m" . PHP_EOL;
        }

        return $output;
    }
}
