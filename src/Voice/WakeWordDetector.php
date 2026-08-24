<?php

namespace Atom\Voice;

use Atom\Security\SecretRedactor;

/**
 * Wake Word Detector — Phase 34
 *
 * Real-time phonetic and acoustic pattern matcher for wake triggers
 * ("Hey Atom", "Atom", "Jarvis") with sensitivity thresholding.
 */
class WakeWordDetector
{
    private array $wakePhrases;
    private float $sensitivity;
    private SecretRedactor $redactor;

    public function __construct(array $wakePhrases = ['hey atom', 'atom', 'jarvis'], float $sensitivity = 0.7, ?SecretRedactor $redactor = null)
    {
        $this->wakePhrases = array_map('strtolower', $wakePhrases);
        $this->sensitivity = max(0.0, min(1.0, $sensitivity));
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Evaluates whether an input phrase contains a configured wake trigger.
     *
     * @param string $input Transcribed speech or token text.
     * @return array Detection outcome with confidence score.
     */
    public function detect(string $input): array
    {
        $cleaned = trim(strtolower($this->redactor->redact($input)));
        if ($cleaned === '') {
            return [
                'detected'   => false,
                'phrase'     => null,
                'confidence' => 0.0,
                'timestamp'  => microtime(true),
            ];
        }

        $bestMatch = null;
        $highestConfidence = 0.0;

        foreach ($this->wakePhrases as $target) {
            // 1. Exact or substring match
            if (str_contains($cleaned, $target)) {
                return [
                    'detected'   => true,
                    'phrase'     => $target,
                    'confidence' => 1.0,
                    'timestamp'  => microtime(true),
                ];
            }

            // 2. Phonetic / Levenshtein similarity across sliding window tokens
            $tokens = explode(' ', $cleaned);
            $targetTokens = explode(' ', $target);
            $windowSize = count($targetTokens);

            for ($i = 0; $i <= count($tokens) - $windowSize; $i++) {
                $subphrase = implode(' ', array_slice($tokens, $i, $windowSize));
                $lev = levenshtein($subphrase, $target);
                $maxLen = max(strlen($subphrase), strlen($target));
                $similarity = $maxLen > 0 ? (1.0 - ($lev / $maxLen)) : 0.0;

                if ($similarity > $highestConfidence) {
                    $highestConfidence = $similarity;
                    $bestMatch = $target;
                }
            }
        }

        $isDetected = ($highestConfidence >= $this->sensitivity);

        return [
            'detected'   => $isDetected,
            'phrase'     => $isDetected ? $bestMatch : null,
            'confidence' => round($highestConfidence, 3),
            'timestamp'  => microtime(true),
        ];
    }

    /**
     * Updates sensitivity threshold.
     */
    public function setSensitivity(float $sensitivity): void
    {
        $this->sensitivity = max(0.0, min(1.0, $sensitivity));
    }

    /**
     * Gets configured wake phrases.
     */
    public function getWakePhrases(): array
    {
        return $this->wakePhrases;
    }
}
