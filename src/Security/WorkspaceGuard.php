<?php

namespace Atom\Security;

class WorkspaceGuard
{
    private string $workspaceRoot;

    public function __construct(string $workspaceRoot)
    {
        $this->workspaceRoot = $this->canonicalize($workspaceRoot);
    }

    /**
     * Resolves a path, removes double slashes, trailing slashes, and checks if it remains in the workspace.
     */
    public function isPathSafe(string $path): bool
    {
        // Prevent null byte attacks
        if (strpos($path, "\0") !== false) {
            return false;
        }

        // Canonicalize target path
        $canonicalPath = $this->canonicalize($path);

        // If target doesn't exist, we still check path prefixes to allow checking safety of files to be created
        if (empty($canonicalPath)) {
            // Fallback: resolve path manually without relying entirely on realpath
            $absolutePath = $this->resolvePathRelative($this->workspaceRoot, $path);
            $canonicalPath = $this->canonicalize($absolutePath);
            if (empty($canonicalPath)) {
                $canonicalPath = $absolutePath;
            }
        }

        // Ensure the path starts with the workspace root
        return strpos($canonicalPath, $this->workspaceRoot) === 0;
    }

    /**
     * Return safe path or throw Exception if unsafe.
     */
    public function getSafePath(string $path): string
    {
        if (!$this->isPathSafe($path)) {
            throw new \SecurityException("Security Error: Target path falls outside the authorized workspace: " . $path);
        }

        $canonical = $this->canonicalize($path);
        if (empty($canonical)) {
            return $this->resolvePathRelative($this->workspaceRoot, $path);
        }
        return $canonical;
    }

    private function canonicalize(string $path): string
    {
        $real = realpath($path);
        if ($real === false) {
            return '';
        }
        // Normalize directory separators to forward slashes for cross-platform matching
        return str_replace('\\', '/', $real);
    }

    private function resolvePathRelative(string $base, string $path): string
    {
        // Simple manual path resolution
        $path = str_replace('\\', '/', $path);
        if (strpos($path, '/') === 0 || preg_match('/^[a-zA-Z]:\//', $path)) {
            // Absolute path
            $parts = explode('/', $path);
        } else {
            // Relative path
            $parts = explode('/', str_replace('\\', '/', $base) . '/' . $path);
        }

        $resolved = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($resolved);
            } else {
                $resolved[] = $part;
            }
        }

        // Reconstruct path
        $prefix = (DIRECTORY_SEPARATOR === '\\') ? '' : '/';
        return $prefix . implode('/', $resolved);
    }
}

// Exception definition if not already defined
if (!class_exists('SecurityException')) {
    class SecurityException extends \Exception {}
}
