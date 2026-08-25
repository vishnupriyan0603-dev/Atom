<?php

namespace App\Controllers\Api;

use Atom\Brain\Voice\SpeechSynthesizer;
use Atom\Brain\Voice\AudioTranscriber;
use Atom\Voice\AudioEqualizerEngine;

/**
 * Voice & Audio Processing API Controller — Phases 24 & Equalizer DSP Engine
 *
 * Endpoints:
 * - POST /api/v1/voice/synthesize         — Synthesize text to speech
 * - POST /api/v1/voice/transcribe         — Transcribe audio to text
 * - GET  /api/v1/voice/voices             — List available voice presets
 * - POST /api/v1/voice/equalizer/apply    — Apply & validate equalizer band settings
 * - GET  /api/v1/voice/equalizer/presets  — Get standard acoustic EQ presets
 * - POST /api/v1/voice/equalizer/curve    — Compute frequency response curve
 * - GET  /api/v1/voice/equalizer/state    — Retrieve active equalizer configuration
 */
class Voice extends BaseApiController
{
    private static ?AudioEqualizerEngine $eqInstance = null;

    private function getEqualizer(): AudioEqualizerEngine
    {
        if (self::$eqInstance === null) {
            self::$eqInstance = new AudioEqualizerEngine();
        }
        return self::$eqInstance;
    }

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

    /**
     * POST /api/v1/voice/equalizer/apply
     */
    public function applyEqualizer()
    {
        $json = $this->request->getJSON(true) ?? [];
        $eq = $this->getEqualizer();

        if (isset($json['preset']) && is_string($json['preset'])) {
            $eq->applyPreset($json['preset']);
        }

        if (isset($json['bands']) && is_array($json['bands'])) {
            $eq->setBands($json['bands']);
        }

        if (isset($json['preamp'])) {
            $eq->setPreamp($json['preamp']);
        }

        if (isset($json['enabled'])) {
            $eq->setEnabled((bool)$json['enabled']);
        }

        if (isset($json['low_cut'])) {
            $lc = $json['low_cut'];
            $eq->setLowCut((bool)($lc['enabled'] ?? false), (float)($lc['frequency'] ?? 80.0));
        }

        if (isset($json['high_cut'])) {
            $hc = $json['high_cut'];
            $eq->setHighCut((bool)($hc['enabled'] ?? false), (float)($hc['frequency'] ?? 12000.0));
        }

        return $this->respondSuccess($eq->getState(), 'Equalizer settings applied successfully');
    }

    /**
     * GET /api/v1/voice/equalizer/presets
     */
    public function getEqualizerPresets()
    {
        $eq = $this->getEqualizer();
        return $this->respondSuccess([
            'bands'   => AudioEqualizerEngine::BANDS,
            'presets' => $eq->getPresets(),
        ], 'Equalizer presets retrieved');
    }

    /**
     * POST /api/v1/voice/equalizer/curve
     */
    public function getEqualizerCurve()
    {
        $json = $this->request->getJSON(true) ?? [];
        $points = max(20, min(200, (int)($json['points'] ?? 80)));
        $eq = $this->getEqualizer();

        return $this->respondSuccess([
            'curve' => $eq->computeFrequencyResponse($points),
            'state' => $eq->getState(),
        ], 'Frequency response curve computed');
    }

    /**
     * GET /api/v1/voice/equalizer/state
     */
    public function getEqualizerState()
    {
        $eq = $this->getEqualizer();
        return $this->respondSuccess($eq->getState(), 'Active equalizer state retrieved');
    }
}
