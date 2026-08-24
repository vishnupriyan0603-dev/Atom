<?php

namespace Atom\Security;

class InputSanitizer
{
    /**
     * Sanitizes file paths preventing directory traversal (../, ..\).
     */
    public static function sanitizeFilePath(string $path): string
    {
        $path = str_replace(["\0", "\r", "\n"], '', $path);
        $path = str_replace('\\', '/', $path);

        // Remove leading drive letters or root slashes for relative sanitization
        $path = preg_replace('/^[a-zA-Z]:\//', '', $path);

        // Strip path traversal sequences
        while (str_contains($path, '../') || str_contains($path, './')) {
            $path = str_replace(['../', './'], '', $path);
        }

        return ltrim($path, '/');
    }

    /**
     * Sanitizes string parameters escaping HTML & script injection tags.
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Validates path safety ensuring target file stays inside workspace root.
     */
    public static function isPathSafe(string $targetPath, string $workspaceRoot): bool
    {
        $realWorkspace = realpath($workspaceRoot);
        if ($realWorkspace === false) {
            return false;
        }

        $fullPath = $workspaceRoot . '/' . self::sanitizeFilePath($targetPath);
        $realTarget = realpath($fullPath);

        if ($realTarget === false) {
            // Check normalized parent directory for new files
            $dir = dirname($fullPath);
            $realDir = realpath($dir);
            return ($realDir !== false && str_starts_with(str_replace('\\', '/', $realDir), str_replace('\\', '/', $realWorkspace)));
        }

        return str_starts_with(str_replace('\\', '/', $realTarget), str_replace('\\', '/', $realWorkspace));
    }
}
