<?php

namespace Atom\Tools;

use Atom\Security\WorkspaceGuard;
use Atom\Security\FilePolicy;

class CreateFileTool implements ToolInterface
{
    private WorkspaceGuard $guard;
    private FilePolicy $policy;
    private PhpLintTool $linter;

    public function __construct(WorkspaceGuard $guard, FilePolicy $policy, PhpLintTool $linter)
    {
        $this->guard = $guard;
        $this->policy = $policy;
        $this->linter = $linter;
    }

    public function getName(): string
    {
        return 'create_file';
    }

    public function execute(array $input): array
    {
        $filePath = $input['file_path'] ?? '';
        $content = $input['content'] ?? '';

        if (empty($filePath)) {
            return [
                'success' => false,
                'error' => 'Missing parameter: file_path'
            ];
        }

        try {
            $safePath = $this->guard->getSafePath($filePath);

            // Validate write extensions
            if (!$this->policy->isAllowed($safePath)) {
                return [
                    'success' => false,
                    'error' => 'Security Policy: Write restricted for this file extension: ' . basename($safePath)
                ];
            }

            // Create directories if missing
            $dir = dirname($safePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Write content
            if (file_put_contents($safePath, $content) === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to write content to file: ' . $filePath
                ];
            }

            // Run syntax check if PHP file
            if (pathinfo($safePath, PATHINFO_EXTENSION) === 'php') {
                $res = $this->linter->execute(['file_path' => $safePath]);
                if (!$res['success']) {
                    // Remove failed file
                    unlink($safePath);
                    return [
                        'success' => false,
                        'error' => 'Lint check failed on new file creation: ' . ($res['error'] ?? $res['output'])
                    ];
                }
            }

            return [
                'success' => true,
                'output' => 'File successfully created: ' . $filePath
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
