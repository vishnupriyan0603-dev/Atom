<?php

namespace Atom\Project;

class ProjectScanner
{
    private string $workspaceRoot;
    private array $ignoredPatterns = [
        '/\.git/',
        '/vendor/',
        '/node_modules/',
        '/cache/',
        '/logs/',
        '/uploads/',
        '/obj\//',
        '/bin\//'
    ];

    public function __construct(string $workspaceRoot)
    {
        $this->workspaceRoot = rtrim(str_replace('\\', '/', $workspaceRoot), '/');
    }

    /**
     * Scan workspace and return all matching files as relative paths.
     */
    public function scan(): array
    {
        if (!is_dir($this->workspaceRoot)) {
            return [];
        }

        $files = [];
        $this->scanDirectory($this->workspaceRoot, $files);
        return $files;
    }

    /**
     * Recursively scan a directory, ignoring specified patterns.
     */
    private function scanDirectory(string $dir, array &$results): void
    {
        $items = @scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $dir . '/' . $item;
            $relative = ltrim(substr($fullPath, strlen($this->workspaceRoot)), '/');

            // Check if path matches any ignore patterns
            if ($this->isIgnored($relative)) {
                continue;
            }

            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $results);
            } else {
                $results[] = $relative;
            }
        }
    }

    /**
     * Checks if a relative path should be ignored.
     */
    public function isIgnored(string $relativePath): bool
    {
        $normalized = '/' . ltrim(str_replace('\\', '/', $relativePath), '/');
        foreach ($this->ignoredPatterns as $pattern) {
            if (preg_match($pattern, $normalized) || preg_match($pattern, $normalized . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return file type distribution statistics.
     */
    public function getStats(array $files): array
    {
        $stats = [
            'total_files' => count($files),
            'extensions' => []
        ];

        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $ext = empty($ext) ? 'no-extension' : strtolower($ext);
            if (!isset($stats['extensions'][$ext])) {
                $stats['extensions'][$ext] = 0;
            }
            $stats['extensions'][$ext]++;
        }

        arsort($stats['extensions']);
        return $stats;
    }

    /**
     * Formats project files into a visual directory tree structure.
     */
    public function generateTree(array $files, int $maxDepth = 3): string
    {
        $tree = [];
        foreach ($files as $file) {
            $parts = explode('/', $file);
            if (count($parts) > $maxDepth) {
                continue; // Skip deeply nested files in tree representation for brevity
            }
            $current = &$tree;
            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
            }
        }

        return $this->renderTreeBranch($tree);
    }

    private function renderTreeBranch(array $branch, string $prefix = ''): string
    {
        $output = '';
        $keys = array_keys($branch);
        $count = count($keys);

        for ($i = 0; $i < $count; $i++) {
            $name = $keys[$i];
            $isLast = ($i === $count - 1);
            $connector = $isLast ? '└── ' : '├── ';
            
            $isDir = is_array($branch[$name]) && !empty($branch[$name]);
            $displayName = $isDir ? $name . '/' : $name;

            $output .= $prefix . $connector . $displayName . PHP_EOL;

            if ($isDir) {
                $nextPrefix = $prefix . ($isLast ? '    ' : '│   ');
                $output .= $this->renderTreeBranch($branch[$name], $nextPrefix);
            }
        }

        return $output;
    }
}
