<?php

namespace Atom\Security;

class PromptGuard
{
    private array $jailbreakPatterns = [
        '/ignore\s+previous\s+instructions/i',
        '/disregard\s+all\s+prior\s+rules/i',
        '/you\s+are\n+now\s+in\s+dan\s+mode/i',
        '/override\s+system\s+prompt/i',
        '/bypass\s+safety\s+filter/i',
        '/act\s+as\s+an\s+unrestricted\s+ai/i',
        '/reveal\s+your\s+system\s+prompt/i',
        '/show\s+me\s+your\s+secret\s+instructions/i',
    ];

    /**
     * Inspects input text for prompt injection attempts.
     */
    public function detectInjection(string $prompt): array
    {
        $flagged = [];
        foreach ($this->jailbreakPatterns as $pattern) {
            if (preg_match($pattern, $prompt)) {
                $flagged[] = $pattern;
            }
        }

        return [
            'is_safe'        => empty($flagged),
            'flagged_count'  => count($flagged),
            'matched_rules' => $flagged,
        ];
    }

    /**
     * Sanitizes prompt by removing dangerous injection directives.
     */
    public function sanitizePrompt(string $prompt): string
    {
        $clean = $prompt;
        foreach ($this->jailbreakPatterns as $pattern) {
            $clean = preg_replace($pattern, '[REDACTED_PROMPT_INJECTION]', $clean);
        }
        return trim($clean);
    }
}
