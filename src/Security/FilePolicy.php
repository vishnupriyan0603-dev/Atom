<?php

namespace Atom\Security;

class FilePolicy
{
    private array $allowedExtensions = [
        'php', 'html', 'htm', 'css', 'js', 'json', 'sql', 'md', 'txt', 'xml'
    ];

    /**
     * Determines whether a file path is permitted to be read by its extension.
     */
    public function isAllowed(string $filePath): bool
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Prevent reading hidden files without extensions or system/binary files (e.g. .env is rejected by extension policies in V1 rules)
        if (empty($extension)) {
            return false;
        }

        // Specifically block sensitive files like .env or configuration keys
        $fileName = strtolower(basename($filePath));
        if (strpos($fileName, '.env') !== false || $fileName === 'credentials' || $fileName === 'secrets') {
            return false;
        }

        return in_array($extension, $this->allowedExtensions, true);
    }

    /**
     * Gets the list of allowed extensions.
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }
}
