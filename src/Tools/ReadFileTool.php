<?php

namespace Atom\Tools;

use Atom\Security\WorkspaceGuard;
use Atom\Security\FilePolicy;
use Atom\Security\SecretRedactor;

class ReadFileTool implements ToolInterface
{
    private WorkspaceGuard $guard;
    private FilePolicy $policy;
    private SecretRedactor $redactor;

    public function __construct(WorkspaceGuard $guard, FilePolicy $policy, SecretRedactor $redactor)
    {
        $this->guard = $guard;
        $this->policy = $policy;
        $this->redactor = $redactor;
    }

    public function getName(): string
    {
        return 'read_file';
    }

    public function execute(array $input): array
    {
        $filePath = $input['file_path'] ?? '';
        if (empty($filePath)) {
            return [
                'success' => false,
                'error' => 'Missing parameter: file_path'
            ];
        }

        try {
            // 1. Guard against directory traversal
            $safePath = $this->guard->getSafePath($filePath);

            // 2. Check extension allowances
            if (!$this->policy->isAllowed($safePath)) {
                return [
                    'success' => false,
                    'error' => "Security Policy: Reading of this file type is restricted: " . basename($safePath)
                ];
            }

            if (!is_file($safePath)) {
                return [
                    'success' => false,
                    'error' => "File not found or is a directory: " . $filePath
                ];
            }

            // 3. Read content
            $content = file_get_contents($safePath);
            if ($content === false) {
                return [
                    'success' => false,
                    'error' => "Unable to read file contents: " . $filePath
                ];
            }

            // 4. Redact credentials prior to outputting
            $redactedContent = $this->redactor->redact($content);

            return [
                'success' => true,
                'content' => $redactedContent
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
