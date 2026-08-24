<?php

namespace Atom\Security;

class SecretRedactor
{
    private array $patterns = [
        // Match standard credential variables and settings
        '/(db_password|db_pass|password|pass|passwd|secret|api_key|apikey|token|smtp_pass|smtp_password|auth_header|session_id|cookie)\s*(=|:|=>)\s*["\']([^"\']+)["\']/i',
        // Match .env type assignments
        '/(DB_PASSWORD|DB_PASS|PASSWORD|SECRET|API_KEY|TOKEN|SMTP_PASSWORD|SMTP_PASS)\s*=\s*([^\r\n]+)/i',
        // Match OpenAI/Anthropic/Google/Groq keys in strings
        '/(sk-[a-zA-Z0-9\-_]{20,}|gsk_[a-zA-Z0-9\-_]{20,}|AIzaSy[a-zA-Z0-9\-_]{30,})/i',
        // Match generic authorization headers
        '/(Authorization:\s*Bearer\s*)([a-zA-Z0-9\._\-]+)/i'
    ];

    /**
     * Redacts secrets in the input text, replacing them with [REDACTED].
     */
    public function redact(string $text): string
    {
        foreach ($this->patterns as $pattern) {
            $text = preg_replace_callback($pattern, function ($matches) {
                // If it is a key-value pattern (3 groups usually)
                if (count($matches) === 4) {
                    return $matches[1] . $matches[2] . '"[REDACTED]"';
                }
                // If it is an env variable assignment (2 groups)
                if (count($matches) === 3) {
                    // Check if the capture is already [REDACTED] to avoid duplicate wrapping
                    if (trim($matches[2]) === '[REDACTED]') {
                        return $matches[0];
                    }
                    return $matches[1] . '=[REDACTED]';
                }
                // If it is direct Bearer token (2 groups)
                if (count($matches) === 2 && strpos($matches[0], 'Bearer') !== false) {
                    return $matches[1] . '[REDACTED]';
                }
                // Fallback direct match replacement
                return '[REDACTED]';
            }, $text);
        }

        return $text;
    }
}
