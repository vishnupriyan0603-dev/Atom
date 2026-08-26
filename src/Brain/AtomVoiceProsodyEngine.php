<?php

namespace Atom\Brain;

use Atom\Security\SecretRedactor;

/**
 * AtomVoiceProsodyEngine — Atom Brain Phase 4
 *
 * Implements:
 * 1. Emotional Speech Prosody Modulation (Pitch, Rate, Volume, Inflection)
 * 2. SSML (Speech Synthesis Markup Language) Generation with pause cadence & emphasis
 * 3. Bilingual Tamil & English Phonetic Calibration
 * 4. Voice Persona Profiles (Heroic Ben 10, Calm Mentor, Empathic Companion, Fast Briefing)
 * 5. Full-Duplex Audio Stream & Interruption Management
 */
class AtomVoiceProsodyEngine
{
    private SecretRedactor $redactor;

    private const VOICE_PROFILES = [
        'heroic_ben10' => [
            'name' => 'Heroic Ben 10 (Tamil/English)',
            'description' => 'Bright, heroic tenor register with energetic velocity, calibrated to Ben 10 reference dialogue.',
            'base_pitch' => 1.18,
            'base_rate' => 1.18,
            'volume' => 1.0,
            'inflection' => 'high_dynamic',
            'default_lang' => 'ta-IN',
            'supported_langs' => ['ta-IN', 'en-IN', 'en-US'],
        ],
        'calm_mentor' => [
            'name' => 'Calm Engineering Mentor',
            'description' => 'Steady, authoritative baritone cadence with clear enunciation for complex architectural explanations.',
            'base_pitch' => 0.95,
            'base_rate' => 1.05,
            'volume' => 0.95,
            'inflection' => 'steady',
            'default_lang' => 'en-US',
            'supported_langs' => ['en-US', 'en-IN', 'ta-IN'],
        ],
        'empathic_companion' => [
            'name' => 'Empathic Companion',
            'description' => 'Warm, gentle, reassuring vocal tone for user support, debugging help, and error recovery.',
            'base_pitch' => 1.02,
            'base_rate' => 0.95,
            'volume' => 0.90,
            'inflection' => 'gentle_curve',
            'default_lang' => 'en-US',
            'supported_langs' => ['en-US', 'en-IN', 'ta-IN'],
        ],
        'fast_briefing' => [
            'name' => 'Ultra-Fast Briefing',
            'description' => 'High-velocity crisp audio synthesizer for rapid summaries, bullet points, and quick answers.',
            'base_pitch' => 1.05,
            'base_rate' => 1.35,
            'volume' => 1.0,
            'inflection' => 'punchy',
            'default_lang' => 'en-US',
            'supported_langs' => ['en-US', 'en-IN', 'ta-IN'],
        ],
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Synthesizes text into complete speech parameters, SSML, and browser synthesis config.
     */
    public function synthesize(string $text, string $profileKey = 'heroic_ben10', string $detectedEmotion = 'neutral'): array
    {
        $cleanText = trim($this->redactor->redact($text));
        if (empty($cleanText)) {
            return [
                'success' => false,
                'error' => 'Input text cannot be empty',
            ];
        }

        // Clean markdown for spoken audio
        $spokenText = $this->prepareSpokenText($cleanText);
        $isTamil = $this->isTamilLanguage($spokenText);

        $profile = self::VOICE_PROFILES[$profileKey] ?? self::VOICE_PROFILES['heroic_ben10'];
        $prosody = $this->calculateProsodyParameters($profile, $detectedEmotion, $isTamil);
        $ssml = $this->generateSsml($spokenText, $prosody, $isTamil);

        return [
            'success' => true,
            'original_text' => $cleanText,
            'spoken_text' => $spokenText,
            'is_tamil' => $isTamil,
            'profile' => [
                'key' => $profileKey,
                'name' => $profile['name'],
                'lang' => $isTamil ? 'ta-IN' : $profile['default_lang'],
            ],
            'prosody' => $prosody,
            'ssml' => $ssml,
            'web_speech_params' => [
                'text' => $spokenText,
                'rate' => $prosody['rate'],
                'pitch' => $prosody['pitch'],
                'volume' => $prosody['volume'],
                'lang' => $isTamil ? 'ta-IN' : ($profileKey === 'heroic_ben10' ? 'en-IN' : $profile['default_lang']),
            ],
        ];
    }

    /**
     * Get all available voice profiles.
     */
    public function getVoiceProfiles(): array
    {
        return self::VOICE_PROFILES;
    }

    /**
     * Manages full-duplex stream audio turns and handles user interruption signals.
     */
    public function handleStreamTurn(string $streamId, string $event, array $payload = []): array
    {
        $validEvents = ['start_speech', 'user_interruption', 'speech_completed', 'pause_stream'];
        if (!in_array($event, $validEvents, true)) {
            return [
                'success' => false,
                'error' => "Unsupported stream event: {$event}",
            ];
        }

        $timestamp = microtime(true);
        $interrupted = ($event === 'user_interruption');

        return [
            'success' => true,
            'stream_id' => $streamId,
            'event' => $event,
            'timestamp' => $timestamp,
            'action' => $interrupted ? 'HALT_AUDIO_PLAYBACK_AND_FLUSH' : 'CONTINUE_STREAM',
            'interruption_backoff_ms' => $interrupted ? 120 : 0,
            'status' => $interrupted ? 'interrupted' : 'streaming',
        ];
    }

    /**
     * Compute dynamic prosody parameters based on base profile, emotion, and language.
     */
    public function calculateProsodyParameters(array $profile, string $emotion = 'neutral', bool $isTamil = false): array
    {
        $pitch = $profile['base_pitch'];
        $rate = $profile['base_rate'];
        $volume = $profile['volume'];

        switch ($emotion) {
            case 'excited':
            case 'happy':
                $pitch = min(2.0, $pitch * 1.08);
                $rate = min(1.5, $rate * 1.06);
                break;
            case 'frustrated':
            case 'confused':
                // Lower pitch slightly, slower rate for clarity and empathy
                $pitch = max(0.8, $pitch * 0.94);
                $rate = max(0.85, $rate * 0.92);
                break;
            case 'playful':
                $pitch = min(2.0, $pitch * 1.05);
                $rate = $rate * 1.02;
                break;
            case 'worried':
                $pitch = max(0.85, $pitch * 0.96);
                $rate = max(0.85, $rate * 0.90);
                break;
        }

        if ($isTamil) {
            // Tamil benefits from slightly higher pitch resonance and calibrated syllable duration
            $pitch = round($pitch * 1.02, 2);
            $rate = round(min(1.25, $rate * 1.02), 2);
        } else {
            $pitch = round($pitch, 2);
            $rate = round($rate, 2);
        }

        return [
            'pitch' => $pitch,
            'rate' => $rate,
            'volume' => round($volume, 2),
            'pitch_ssml' => sprintf("%+d%%", round(($pitch - 1.0) * 100)),
            'rate_ssml' => sprintf("%+d%%", round(($rate - 1.0) * 100)),
            'emotion_calibrated' => $emotion,
        ];
    }

    /**
     * Generate standard W3C-compliant SSML string with safe XML escaping.
     */
    public function generateSsml(string $cleanSpokenText, array $prosody, bool $isTamil = false): string
    {
        // Safe XML escape
        $escapedText = htmlspecialchars($cleanSpokenText, ENT_XML1, 'UTF-8');

        // Add natural pause breaks after sentences and commas
        $escapedText = preg_replace('/([.!?])\s+/u', '$1 <break time="280ms"/> ', $escapedText);
        $escapedText = preg_replace('/([,;:])\s+/u', '$1 <break time="140ms"/> ', $escapedText);

        $lang = $isTamil ? 'ta-IN' : 'en-US';
        $pitchAttr = $prosody['pitch_ssml'];
        $rateAttr = $prosody['rate_ssml'];

        return "<speak version=\"1.0\" xmlns=\"http://www.w3.org/2001/10/synthesis\" xml:lang=\"{$lang}\">"
             . "<prosody pitch=\"{$pitchAttr}\" rate=\"{$rateAttr}\">"
             . $escapedText
             . "</prosody>"
             . "</speak>";
    }

    /**
     * Check if text contains Tamil Unicode script characters (\u{0B80}-\u{0BFF}).
     */
    public function isTamilLanguage(string $text): bool
    {
        return (bool) preg_match('/[\x{0B80}-\x{0BFF}]/u', $text);
    }

    /**
     * Strips markdown code blocks, backticks, bold symbols, links to make text audio-friendly.
     */
    public function prepareSpokenText(string $text): string
    {
        // Strip code blocks with a clean audio placeholder
        $clean = preg_replace('/```[a-zA-Z0-9_\-]*\n[\s\S]*?```/u', 'Code block omitted for audio.', $text);
        // Strip inline code
        $clean = preg_replace('/`([^`]+)`/u', '$1', $clean);
        // Strip markdown links [label](url) -> label
        $clean = preg_replace('/\[([^\]]+)\]\([^\)]+\)/u', '$1', $clean);
        // Strip formatting markdown symbols * _ ~ # >
        $clean = preg_replace('/[*_~#>]/u', '', $clean);
        // Replace multiple whitespace/newlines with single space
        $clean = preg_replace('/\s+/u', ' ', $clean);

        return trim($clean);
    }
}
