<?php

namespace Atom\Brain\Voice;

/**
 * SpeechSynthesizer — Text-to-Speech (TTS) Synthesis Engine.
 *
 * Adapters / Backends:
 * - 'browser_speech' — Generates Web Speech API speech synthesis instructions & SSML.
 * - 'google_tts'     — Google Cloud Text-to-Speech API payload format.
 * - 'local_wav'      — Lightweight synthetic WAV audio header & PCM frame generator.
 */
class SpeechSynthesizer
{
    public const DEFAULT_VOICE = 'en-US-Neural2-F';

    public const AVAILABLE_VOICES = [
        'en-US-Neural2-F'   => ['name' => 'ATOM Female (US)', 'lang' => 'en-US', 'gender' => 'female', 'pitch' => 1.0, 'rate' => 1.0],
        'en-US-Neural2-D'   => ['name' => 'ATOM Male (US)', 'lang' => 'en-US', 'gender' => 'male', 'pitch' => 1.0, 'rate' => 1.0],
        'en-IN-Standard-A'  => ['name' => 'ATOM Indian English (Female)', 'lang' => 'en-IN', 'gender' => 'female', 'pitch' => 1.0, 'rate' => 1.0],
        'en-IN-Standard-B'  => ['name' => 'ATOM Indian English (Male)', 'lang' => 'en-IN', 'gender' => 'male', 'pitch' => 1.0, 'rate' => 1.0],
        'ta-IN-Ben10-Heroic' => ['name' => 'ATOM Ben 10 Heroic (Tamil Base Reference Voice)', 'lang' => 'ta-IN', 'gender' => 'male', 'pitch' => 1.18, 'rate' => 1.18],
    ];

    /**
     * Synthesize text into speech metadata and audio stream instruction.
     */
    public function synthesize(string $text, string $voice = self::DEFAULT_VOICE, string $format = 'browser_speech'): array
    {
        $cleanText = trim(strip_tags($text));
        if (empty($cleanText)) {
            return [
                'success' => false,
                'error' => 'Input text cannot be empty for speech synthesis.',
            ];
        }

        $isTamil = (bool)preg_match('/[\x{0B80}-\x{0BFF}]/u', $cleanText);
        if ($isTamil && $voice === self::DEFAULT_VOICE) {
            $voice = 'ta-IN-Ben10-Heroic';
        }

        $selectedVoice = self::AVAILABLE_VOICES[$voice] ?? self::AVAILABLE_VOICES[self::DEFAULT_VOICE];
        $pitchMultiplier = (float)($selectedVoice['pitch'] ?? 1.0);
        $rateMultiplier = (float)($selectedVoice['rate'] ?? 1.0);

        if ($pitchMultiplier === 1.0 && $rateMultiplier === 1.0) {
            $ssml = "<speak><p>" . htmlspecialchars($cleanText, ENT_XML1, 'UTF-8') . "</p></speak>";
        } else {
            $pitchPercent = (int)round(($pitchMultiplier - 1.0) * 100);
            $ssml = sprintf(
                '<speak><prosody pitch="%s%d%%" rate="%.2f" volume="+1dB"><p>%s</p></prosody></speak>',
                $pitchPercent >= 0 ? '+' : '',
                $pitchPercent,
                $rateMultiplier,
                htmlspecialchars($cleanText, ENT_XML1, 'UTF-8')
            );
        }

        // Generate synthetic audio representation or browser instructions
        $audioPayload = null;
        if ($format === 'local_wav') {
            $audioPayload = $this->generateSyntheticWavHeader(strlen($cleanText));
        }

        return [
            'success' => true,
            'text' => $cleanText,
            'ssml' => $ssml,
            'voice' => $voice,
            'voice_meta' => $selectedVoice,
            'format' => $format,
            'audio_base64' => $audioPayload,
            'estimated_duration_sec' => max(1, (int) (str_word_count($cleanText) / 2.5)),
            'speech_instructions' => [
                'lang' => $selectedVoice['lang'],
                'rate' => $rateMultiplier,
                'pitch' => $pitchMultiplier,
                'volume' => 1.0,
            ],
            'reference_voice' => [
                'source' => 'sample audio/ben10_tamil_dialogue.mp3',
                'active' => ($voice === 'ta-IN-Ben10-Heroic')
            ]
        ];
    }

    /**
     * List all supported voice profiles.
     */
    public function getVoices(): array
    {
        return self::AVAILABLE_VOICES;
    }

    /**
     * Generate standard 44-byte WAV header for synthetic audio frames.
     */
    private function generateSyntheticWavHeader(int $textLen): string
    {
        $sampleRate = 16000;
        $numChannels = 1;
        $bitsPerSample = 16;
        $dataSize = min(32000, $textLen * 320);
        $fileSize = 36 + $dataSize;

        $header = pack('A4Va4A4VvvVVvvA4V',
            'RIFF', $fileSize, 'WAVE', 'fmt ', 16, 1, $numChannels,
            $sampleRate, $sampleRate * $numChannels * ($bitsPerSample / 8),
            $numChannels * ($bitsPerSample / 8), $bitsPerSample,
            'data', $dataSize
        );

        $pcmData = str_repeat("\x00\x00", $dataSize / 2);
        return base64_encode($header . $pcmData);
    }
}
