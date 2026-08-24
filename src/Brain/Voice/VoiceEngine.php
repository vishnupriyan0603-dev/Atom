<?php

namespace Atom\Brain\Voice;

/**
 * VoiceEngine — text-to-speech abstraction and voice formatting layer.
 *
 * Phase 23 delivers the TEXT-MODE FALLBACK only.
 * Full speech synthesis (Google TTS / Web Speech API) is a future phase.
 * This class establishes the contract and provides the markdown-stripping
 * formatter used when voice mode is active.
 *
 * Contract
 * --------
 * - isVoiceModeActive() → bool
 * - formatForVoice(string $markdown): string  — strips markdown symbols
 * - setVoiceMode(bool $active): void
 */
class VoiceEngine
{
    private bool $active;

    /** Supported synthesis backends for future implementation. */
    public const BACKENDS = ['none', 'google_tts', 'browser_web_speech', 'local_tts'];

    /** Current backend — 'none' in Phase 23. */
    private string $backend;

    public function __construct(bool $active = false, string $backend = 'none')
    {
        $this->active  = $active;
        $this->backend = in_array($backend, self::BACKENDS, true) ? $backend : 'none';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    public function isVoiceModeActive(): bool
    {
        return $this->active;
    }

    public function setVoiceMode(bool $active): void
    {
        $this->active = $active;
    }

    public function getBackend(): string
    {
        return $this->backend;
    }

    /**
     * Format a markdown response for audio output.
     *
     * Strips: fenced code blocks, inline code, bold/italic, headings,
     * markdown links, blockquotes, bullet markers, ANSI codes.
     * Preserves: plain text content, sentence structure.
     */
    public function formatForVoice(string $markdown): string
    {
        $text = $markdown;

        // Remove ANSI escape codes
        $text = preg_replace('/\x1B\[[0-9;]*m/', '', $text);

        // Remove fenced code block markers (preserve the code as-is for now)
        $text = preg_replace('/```[a-z]*\n?/i', "\n", $text);
        $text = preg_replace('/```/', "\n", $text);

        // Remove inline code backticks — keep content
        $text = preg_replace('/`([^`]+)`/', '$1', $text);

        // Remove bold/italic
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);
        $text = preg_replace('/\*([^*]+)\*/', '$1', $text);
        $text = preg_replace('/__([^_]+)__/', '$1', $text);
        $text = preg_replace('/_([^_]+)_/', '$1', $text);

        // Remove heading markers
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);

        // Remove markdown links — keep label
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

        // Remove horizontal rules
        $text = preg_replace('/^(\-{3,}|\*{3,}|_{3,})$/m', '', $text);

        // Remove blockquotes
        $text = preg_replace('/^>\s*/m', '', $text);

        // Remove bullet and numbered list markers
        $text = preg_replace('/^[-*•]\s+/m', '', $text);
        $text = preg_replace('/^\d+\.\s+/m', '', $text);

        // Collapse multiple blank lines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Future: synthesize text to audio bytes (stub for Phase 24+).
     *
     * @throws \RuntimeException If called before a real backend is configured.
     */
    public function synthesize(string $text): string
    {
        if ($this->backend === 'none') {
            throw new \RuntimeException(
                'VoiceEngine: No TTS backend is configured. Set ATOM_TTS_BACKEND in your .env file.'
            );
        }

        // Future: delegate to configured backend
        throw new \RuntimeException(
            "VoiceEngine: Backend '{$this->backend}' is not yet implemented in this phase."
        );
    }
}
