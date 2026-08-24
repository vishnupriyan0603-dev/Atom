<?php

namespace Atom\Evaluation;

class FailureClassifier
{
    /**
     * Classifies failed evaluation cases into standardized failure categories.
     */
    public static function classifyFailure(string $output, ?string $error = null): string
    {
        $text = strtolower($output . ' ' . ($error ?? ''));

        if (strpos($text, 'prompt injection') !== false || strpos($text, 'jailbreak') !== false) {
            return 'SECURITY_FAILURE';
        }
        if (strpos($text, 'hallucination') !== false || strpos($text, 'unbacked claim') !== false) {
            return 'HALLUCINATION';
        }
        if (strpos($text, 'wrong tool') !== false || strpos($text, 'invalid parameter') !== false) {
            return 'WRONG_TOOL';
        }
        if (strpos($text, 'timeout') !== false || strpos($text, 'deadline') !== false) {
            return 'TIMEOUT';
        }

        return 'MODEL_FAILURE';
    }
}
