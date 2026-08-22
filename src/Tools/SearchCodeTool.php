<?php

namespace Atom\Tools;

use Atom\Project\ProjectScanner;
use Atom\Project\CodeSearch;
use Atom\Security\SecretRedactor;

class SearchCodeTool implements ToolInterface
{
    private ProjectScanner $scanner;
    private CodeSearch $searcher;
    private SecretRedactor $redactor;

    public function __construct(ProjectScanner $scanner, CodeSearch $searcher, SecretRedactor $redactor)
    {
        $this->scanner = $scanner;
        $this->searcher = $searcher;
        $this->redactor = $redactor;
    }

    public function getName(): string
    {
        return 'search_code';
    }

    public function execute(array $input): array
    {
        $query = $input['query'] ?? '';
        if (empty($query)) {
            return [
                'success' => false,
                'error' => 'Missing parameter: query'
            ];
        }

        // Get indexed files list
        $files = $this->scanner->scan();
        $results = $this->searcher->search($query, $files);

        // Run secret redactor on content matches
        if (!empty($results['contents'])) {
            foreach ($results['contents'] as &$fileMatch) {
                foreach ($fileMatch['matches'] as &$lineMatch) {
                    $lineMatch['text'] = $this->redactor->redact($lineMatch['text']);
                }
            }
        }

        return [
            'success' => true,
            'results' => $results
        ];
    }
}
