<?php

namespace Atom\Tools;

use Atom\Arc\ArcOneGitController;

/**
 * Tool representation of ARC ONE Local Git Operations.
 */
class ArcGitTool implements ToolInterface
{
    private ArcOneGitController $controller;

    public function __construct(?ArcOneGitController $controller = null)
    {
        $this->controller = $controller ?? new ArcOneGitController();
    }

    public function getName(): string
    {
        return 'arc_git';
    }

    public function execute(array $input): array
    {
        $action = strtolower((string)($input['action'] ?? 'status'));

        switch ($action) {
            case 'status':
                return $this->controller->status();

            case 'changed_files':
            case 'changes':
                return $this->controller->changedFiles();

            case 'diff':
                return $this->controller->diff(
                    (bool)($input['staged'] ?? false),
                    isset($input['file']) ? (string)$input['file'] : null
                );

            case 'stage':
            case 'add':
                return $this->controller->stage($input['files'] ?? '.');

            case 'commit':
                return $this->controller->commit((string)($input['message'] ?? ''));

            case 'is_repo':
                return $this->controller->isGitRepository();

            default:
                return [
                    'success' => false,
                    'error' => "Unknown arc_git action '{$action}'. Supported: status, changed_files, diff, stage, commit, is_repo.",
                ];
        }
    }
}

