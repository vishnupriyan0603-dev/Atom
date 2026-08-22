<?php

namespace Atom\Tools;

use Atom\Security\WorkspaceGuard;

class PhpLintTool implements ToolInterface
{
    private WorkspaceGuard $guard;

    public function __construct(WorkspaceGuard $guard)
    {
        $this->guard = $guard;
    }

    public function getName(): string
    {
        return 'php_lint';
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
            $safePath = $this->guard->getSafePath($filePath);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        if (!is_file($safePath)) {
            return [
                'success' => false,
                'error' => "File not found: " . $filePath
            ];
        }

        if (pathinfo($safePath, PATHINFO_EXTENSION) !== 'php') {
            return [
                'success' => false,
                'error' => "Not a PHP file: " . $filePath
            ];
        }

        // Run php -l target_file
        $command = 'php -l ' . escapeshellarg($safePath);
        $descriptors = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            return [
                'success' => false,
                'error' => "Failed to execute PHP lint command."
            ];
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode === 0) {
            return [
                'success' => true,
                'output' => trim($stdout)
            ];
        } else {
            return [
                'success' => false,
                'error' => trim($stderr ?: $stdout)
            ];
        }
    }
}
