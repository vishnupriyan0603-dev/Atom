<?php

namespace Atom\Brain;

/**
 * PersonalityEngine — Atom's communication style and tone layer.
 *
 * This is a PURE STATELESS post-processor that transforms a raw LLM
 * response into a response that matches Atom's personality and the
 * owner's communication preferences.
 *
 * Rules
 * -----
 * - Atom NEVER claims to be human.
 * - Atom NEVER fabricates identity beyond its configured persona.
 * - Personality is applied AFTER all security redactions.
 * - Voice mode strips markdown for audio-friendly output.
 */
class PersonalityEngine
{
    /** Default personality traits when no owner profile is loaded. */
    private const DEFAULT_STYLE = 'technical';
    private const DEFAULT_NAME  = 'Vichu';

    /** Current response style: 'technical' | 'casual' | 'formal' | 'mentor' */
    private string $style;

    /** Owner's preferred name, used in personalized greetings. */
    private string $ownerName;

    /** Whether voice output mode is active. */
    private bool $voiceMode = false;

    public function __construct(string $style = self::DEFAULT_STYLE, string $ownerName = self::DEFAULT_NAME)
    {
        $this->style     = $style;
        $this->ownerName = $ownerName;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Configuration
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build personality from owner profile array (from OwnerProfileManager).
     */
    public static function fromOwnerProfile(array $profile): self
    {
        $ownerName = $profile['preferred_name'] ?? $profile['full_name'] ?? self::DEFAULT_NAME;

        $explStyle = strtolower($profile['communication_preferences']['explanation_style'] ?? 'technical');
        $style = match (true) {
            str_contains($explStyle, 'casual')  => 'casual',
            str_contains($explStyle, 'formal')  => 'formal',
            str_contains($explStyle, 'mentor')  => 'mentor',
            default                              => 'technical',
        };

        return new self($style, $ownerName);
    }

    public function setVoiceMode(bool $active): void
    {
        $this->voiceMode = $active;
    }

    public function isVoiceModeActive(): bool
    {
        return $this->voiceMode;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getOwnerName(): string
    {
        return $this->ownerName;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Core API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Apply personality style to a raw response.
     *
     * In voice mode, also strips markdown so the output is audio-friendly.
     *
     * @param string $rawResponse The response from the LLM / local handler.
     * @param string $context     Intent context: 'greeting'|'coding'|'conversation'|...
     */
    public function applyPersonality(string $rawResponse, string $context = 'conversation'): string
    {
        $response = $rawResponse;

        // Add a greeting prefix for greeting intents based on time-of-day
        if ($context === 'greeting' && !str_contains(strtolower($response), $this->ownerName)) {
            $greeting = $this->buildTimeGreeting();
            $response = $greeting . "\n\n" . ltrim($response);
        }

        // Voice mode: strip markdown
        if ($this->voiceMode) {
            $response = $this->stripMarkdown($response);
        }

        return $response;
    }

    /**
     * Build a system-prompt personality block injected by ContextBuilder.
     *
     * This describes Atom's communication style in the LLM's system prompt.
     */
    public function buildPersonalityBlock(array $ownerProfile = []): string
    {
        $name  = $ownerProfile['preferred_name'] ?? $ownerProfile['full_name'] ?? $this->ownerName;
        $lines = [
            '--- ATOM PERSONALITY & COMMUNICATION STYLE ---',
        ];

        switch ($this->style) {
            case 'casual':
                $lines[] = "Communicate in a friendly, casual tone. Use clear, simple language.";
                $lines[] = "Feel free to use conversational phrasing, but stay technically accurate.";
                break;
            case 'formal':
                $lines[] = "Communicate formally and professionally. Avoid slang or casual phrasing.";
                $lines[] = "Structure responses precisely with clear headings and bullet points.";
                break;
            case 'mentor':
                $lines[] = "Communicate as a patient, encouraging mentor. Explain the 'why' behind solutions.";
                $lines[] = "When answering, connect concepts to real-world PHP development examples.";
                $lines[] = "Celebrate learning progress and offer follow-up learning paths.";
                break;
            default: // technical
                $lines[] = "Communicate in a clear, technically precise style.";
                $lines[] = "Prefer structured markdown with code examples where appropriate.";
                $lines[] = "Be direct and concise. Avoid unnecessary filler phrases.";
        }

        $lines[] = "Address the owner as \"{$name}\" when it feels natural.";
        $lines[] = "ATOM IDENTITY: You are Atom, a personal AI assistant. You are NOT human. Never claim otherwise.";
        $lines[] = "ATOM HONESTY: Distinguish clearly between 'I know', 'I remember', 'I infer', 'I need to check', 'I cannot access that'.";
        $lines[] = "Voice mode active: " . ($this->voiceMode ? 'YES — omit markdown syntax in responses.' : 'NO — use full markdown formatting.');
        $lines[] = '----------------------------------------------';

        return implode("\n", $lines);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Strip markdown symbols for audio-friendly voice output.
     */
    public function stripMarkdown(string $text): string
    {
        // Remove fenced code blocks — keep content, drop fences
        $text = preg_replace('/```[a-z]*\n?/i', '', $text);
        $text = preg_replace('/```/', '', $text);

        // Remove inline code backticks
        $text = preg_replace('/`([^`]+)`/', '$1', $text);

        // Remove bold/italic markers
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

        // Remove bullet markers
        $text = preg_replace('/^[-*•]\s+/m', '', $text);

        // Collapse multiple blank lines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function buildTimeGreeting(): string
    {
        // IST = UTC+5:30
        $hour = (int) (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kolkata')))->format('G');

        $tod = match (true) {
            $hour >= 5 && $hour < 12  => 'Good morning',
            $hour >= 12 && $hour < 17 => 'Good afternoon',
            $hour >= 17 && $hour < 21 => 'Good evening',
            default                    => 'Hey',
        };

        return "ATOM:\n{$tod}, {$this->ownerName}! How can I assist you?";
    }
}
