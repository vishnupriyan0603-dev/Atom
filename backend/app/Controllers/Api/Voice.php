<?php

namespace App\Controllers\Api;

use Atom\Brain\Voice\SpeechSynthesizer;
use Atom\Brain\Voice\AudioTranscriber;

/**
 * Voice API Controller — Phase 24
 *
 * Endpoints:
 * - POST /api/v1/voice/synthesize — Synthesize text to speech
 * - POST /api/v1/voice/transcribe — Transcribe audio to text
 * - GET  /api/v1/voice/voices     — List available voice presets
 */
class Voice extends BaseApiController
{
    /**
     * POST /api/v1/voice/synthesize
     */
    public function synthesize()
    {
        $json = $this->request->getJSON(true) ?? [];
        $text = trim($json['text'] ?? '');
        $voice = $json['voice'] ?? SpeechSynthesizer::DEFAULT_VOICE;
        $format = $json['format'] ?? 'browser_speech';

        if (empty($text)) {
            return $this->respondError('Missing or empty text parameter', 400);
        }

        $synthesizer = new SpeechSynthesizer();
        $result = $synthesizer->synthesize($text, $voice, $format);

        if (!$result['success']) {
            return $this->respondError($result['error'] ?? 'Synthesis failed', 400);
        }

        return $this->respondSuccess($result, 'Speech synthesized successfully');
    }

    /**
     * POST /api/v1/voice/transcribe
     */
    public function transcribe()
    {
        $json = $this->request->getJSON(true) ?? [];
        $audioData = $json['audio_data'] ?? '';
        $language = $json['language'] ?? 'en';
        $mimeType = $json['mime_type'] ?? 'audio/webm';

        if (empty($audioData)) {
            return $this->respondError('Missing audio_data parameter', 400);
        }

        $transcriber = new AudioTranscriber();
        $result = $transcriber->transcribe($audioData, $language, $mimeType);

        if (!$result['success']) {
            return $this->respondError($result['error'] ?? 'Transcription failed', 400);
        }

        return $this->respondSuccess($result, 'Audio transcribed successfully');
    }

    /**
     * GET /api/v1/voice/voices
     */
    public function getVoices()
    {
        $synthesizer = new SpeechSynthesizer();
        return $this->respondSuccess([
            'default_voice' => SpeechSynthesizer::DEFAULT_VOICE,
            'voices' => $synthesizer->getVoices(),
        ], 'Voice list retrieved');
    }
}
