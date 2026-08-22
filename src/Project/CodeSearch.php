<?php

namespace Atom\Project;

class CodeSearch
{
    private string $workspaceRoot;
    private ProjectScanner $scanner;

    public function __construct(string $workspaceRoot, ProjectScanner $scanner)
    {
        $this->workspaceRoot = rtrim(str_replace('\\', '/', $workspaceRoot), '/');
        $this->scanner = $scanner;
    }

    /**
     * Search file names or file contents.
     */
    public function search(string $query, array $files): array
    {
        $results = [
            'filenames' => [],
            'contents' => []
        ];

        if (empty($query)) {
            return $results;
        }

        $queryLower = strtolower($query);

        foreach ($files as $relative) {
            // Match file name
            if (strpos(strtolower(basename($relative)), $queryLower) !== false) {
                $results['filenames'][] = $relative;
            }

            // Match content
            $fullPath = $this->workspaceRoot . '/' . $relative;
            if (!is_file($fullPath) || filesize($fullPath) > 1024 * 1024) { // Skip files larger than 1MB
                continue;
            }

            $content = @file_get_contents($fullPath);
            if ($content === false) {
                continue;
            }

            // Check case-insensitive content match
            if (stripos($content, $query) !== false) {
                $lines = explode("\n", $content);
                $matchesInFile = [];
                foreach ($lines as $index => $line) {
                    if (stripos($line, $query) !== false) {
                        $matchesInFile[] = [
                            'line' => $index + 1,
                            'text' => trim($line)
                        ];
                        // Limit matched lines per file to prevent blowing up the display
                        if (count($matchesInFile) >= 5) {
                            break;
                        }
                    }
                }
                if (!empty($matchesInFile)) {
                    $results['contents'][] = [
                        'file' => $relative,
                        'matches' => $matchesInFile
                    ];
                }
            }
        }

        return $results;
    }
}
