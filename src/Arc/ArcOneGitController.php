<?php

namespace Atom\Arc;

/**
 * ARC ONE — Git Controller
 *
 * Responsible strictly for local Git repository operations:
 * - Repository detection (isGitRepository)
 * - Status & changed files inspection (status, changedFiles)
 * - Diff summary generation (diff)
 * - Explicit staging (stage)
 * - Explicit committing (commit)
 *
 * Safety Rules:
 * - Never pushes to remote repositories.
 * - Never modifies unrelated files or performs arbitrary command execution.
 * - Returns structured results from every operation.
 */
class ArcOneGitController
{
    private string $repoPath;
    private array $actionLog = [];

    public function __construct(?string $repoPath = null)
    {
        $this->repoPath = $repoPath ? realpath($repoPath) ?: $repoPath : getcwd();
    }

    public function getRepoPath(): string
    {
        return $this->repoPath;
    }

    public function getActionLog(): array
    {
        return $this->actionLog;
    }

    /**
     * Check if directory is inside a valid Git repository work tree.
     */
    public function isGitRepository(): array
    {
        $result = $this->runGitCommand('rev-parse --is-inside-work-tree');
        $isRepo = ($result['exit_code'] === 0 && trim($result['stdout']) === 'true');

        return $this->logResult([
            'success' => true,
            'action' => 'is_git_repository',
            'is_repo' => $isRepo,
            'repo_path' => $this->repoPath,
        ]);
    }

    /**
     * Run git status and return structured clean/dirty state with branches & files.
     */
    public function status(): array
    {
        $isRepo = $this->isGitRepository();
        if (!$isRepo['is_repo']) {
            return $this->logResult([
                'success' => false,
                'action' => 'status',
                'error' => "Directory '{$this->repoPath}' is not a Git repository.",
            ]);
        }

        // Branch name
        $branchRes = $this->runGitCommand('rev-parse --abbrev-ref HEAD');
        $branch = ($branchRes['exit_code'] === 0) ? trim($branchRes['stdout']) : 'unknown';

        // Porcelain status
        $statusRes = $this->runGitCommand('status --porcelain');
        if ($statusRes['exit_code'] !== 0) {
            return $this->logResult([
                'success' => false,
                'action' => 'status',
                'error' => $statusRes['stderr'] ?: 'Failed to query git status.',
            ]);
        }

        $lines = array_filter(explode("\n", trim($statusRes['stdout'])));
        $staged = [];
        $unstaged = [];
        $untracked = [];

        foreach ($lines as $line) {
            if (strlen($line) < 3) continue;
            $x = $line[0]; // Staged status
            $y = $line[1]; // Unstaged status
            $file = trim(substr($line, 2));

            if ($x === '?' && $y === '?') {
                $untracked[] = $file;
            } else {
                if ($x !== ' ' && $x !== '?') {
                    $staged[] = ['status' => $x, 'file' => $file];
                }
                if ($y !== ' ' && $y !== '?') {
                    $unstaged[] = ['status' => $y, 'file' => $file];
                }
            }
        }

        $isClean = (empty($staged) && empty($unstaged) && empty($untracked));

        return $this->logResult([
            'success' => true,
            'action' => 'status',
            'branch' => $branch,
            'clean' => $isClean,
            'staged_count' => count($staged),
            'unstaged_count' => count($unstaged),
            'untracked_count' => count($untracked),
            'staged' => $staged,
            'unstaged' => $unstaged,
            'untracked' => $untracked,
        ]);
    }

    /**
     * Show all changed files in the working directory and staging area.
     */
    public function changedFiles(): array
    {
        $status = $this->status();
        if (!$status['success']) {
            return $status;
        }

        $allFiles = [];
        foreach ($status['staged'] as $item) {
            $allFiles[] = ['scope' => 'staged', 'status' => $item['status'], 'file' => $item['file']];
        }
        foreach ($status['unstaged'] as $item) {
            $allFiles[] = ['scope' => 'unstaged', 'status' => $item['status'], 'file' => $item['file']];
        }
        foreach ($status['untracked'] as $file) {
            $allFiles[] = ['scope' => 'untracked', 'status' => '?', 'file' => $file];
        }

        return $this->logResult([
            'success' => true,
            'action' => 'changed_files',
            'total_changed' => count($allFiles),
            'files' => $allFiles,
        ]);
    }

    /**
     * Show a diff summary or full patch diff.
     */
    public function diff(bool $staged = false, ?string $file = null): array
    {
        $args = $staged ? 'diff --cached' : 'diff';
        if ($file !== null) {
            $safeFile = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\]/', '', $file);
            $args .= ' -- ' . escapeshellarg($safeFile);
        }

        $statRes = $this->runGitCommand("{$args} --stat");
        $patchRes = $this->runGitCommand($args);

        if ($patchRes['exit_code'] !== 0) {
            return $this->logResult([
                'success' => false,
                'action' => 'diff',
                'error' => $patchRes['stderr'] ?: 'Failed to generate diff.',
            ]);
        }

        return $this->logResult([
            'success' => true,
            'action' => 'diff',
            'staged' => $staged,
            'target_file' => $file,
            'summary' => trim($statRes['stdout']),
            'has_diff' => !empty(trim($patchRes['stdout'])),
            'diff' => trim($patchRes['stdout']),
        ]);
    }

    /**
     * Stage files when explicitly requested.
     */
    public function stage(array|string $files = '.'): array
    {
        $fileList = is_array($files) ? $files : [$files];
        $sanitized = [];

        foreach ($fileList as $f) {
            if ($f === '.') {
                $sanitized[] = '.';
            } else {
                $clean = preg_replace('/[^a-zA-Z0-9_\-\.\/\\\]/', '', $f);
                if (!empty($clean)) $sanitized[] = escapeshellarg($clean);
            }
        }

        if (empty($sanitized)) {
            return $this->logResult([
                'success' => false,
                'action' => 'stage',
                'error' => 'No valid files specified to stage.',
            ]);
        }

        $cmd = 'add ' . implode(' ', $sanitized);
        $res = $this->runGitCommand($cmd);

        if ($res['exit_code'] !== 0) {
            return $this->logResult([
                'success' => false,
                'action' => 'stage',
                'error' => $res['stderr'] ?: 'Failed to stage files.',
            ]);
        }

        return $this->logResult([
            'success' => true,
            'action' => 'stage',
            'staged_targets' => is_array($files) ? $files : [$files],
            'message' => 'Files staged successfully.',
        ]);
    }

    /**
     * Create a Git commit when explicitly requested.
     */
    public function commit(string $message): array
    {
        $cleanMsg = trim($message);
        if (empty($cleanMsg)) {
            return $this->logResult([
                'success' => false,
                'action' => 'commit',
                'error' => 'Commit message cannot be empty.',
            ]);
        }

        $escapedMsg = escapeshellarg($cleanMsg);
        $res = $this->runGitCommand("commit -m {$escapedMsg}");

        if ($res['exit_code'] !== 0) {
            return $this->logResult([
                'success' => false,
                'action' => 'commit',
                'error' => $res['stderr'] ?: $res['stdout'] ?: 'Commit failed (possibly nothing to commit).',
            ]);
        }

        // Get latest commit hash
        $hashRes = $this->runGitCommand('rev-parse HEAD');
        $commitHash = ($hashRes['exit_code'] === 0) ? trim($hashRes['stdout']) : null;

        return $this->logResult([
            'success' => true,
            'action' => 'commit',
            'commit_hash' => $commitHash,
            'message' => $cleanMsg,
            'output' => trim($res['stdout']),
        ]);
    }

    /**
     * Executes a safe git sub-command in the configured repository directory.
     */
    private function runGitCommand(string $args): array
    {
        if (!is_dir($this->repoPath)) {
            return [
                'exit_code' => -1,
                'stdout' => '',
                'stderr' => "Directory '{$this->repoPath}' does not exist.",
            ];
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = "git {$args}";
        $process = proc_open($cmd, $descriptors, $pipes, $this->repoPath);

        if (!is_resource($process)) {
            return [
                'exit_code' => -1,
                'stdout' => '',
                'stderr' => 'Failed to spawn git process.',
            ];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => $stdout ?: '',
            'stderr' => $stderr ?: '',
        ];
    }

    private function logResult(array $result): array
    {
        $result['timestamp'] = date('c');
        $this->actionLog[] = $result;
        return $result;
    }
}

