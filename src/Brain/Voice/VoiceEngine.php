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
    private SpeechSynthesizer $synthesizer;
    private AudioTranscriber $transcriber;

    public function __construct(bool $active = false, string $backend = 'browser_web_speech')
    {
        $this->active      = $active;
        $this->backend     = in_array($backend, self::BACKENDS, true) ? $backend : 'browser_web_speech';
        $this->synthesizer = new SpeechSynthesizer();
        $this->transcriber = new AudioTranscriber();
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

    public function getSynthesizer(): SpeechSynthesizer
    {
        return $this->synthesizer;
    }

    public function getTranscriber(): AudioTranscriber
    {
        return $this->transcriber;
    }

    /**
     * Synthesize text into speech instructions or synthetic audio.
     */
    public function synthesize(string $text, string $voice = SpeechSynthesizer::DEFAULT_VOICE): array
    {
        return $this->synthesizer->synthesize($text, $voice, $this->backend === 'local_tts' ? 'local_wav' : 'browser_speech');
    }

    /**
     * Transcribe audio into text.
     */
    public function transcribe(string $audioDataOrBase64, string $language = 'en'): array
    {
        return $this->transcriber->transcribe($audioDataOrBase64, $language);
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
}
