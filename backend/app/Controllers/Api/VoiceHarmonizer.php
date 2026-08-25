<?php

namespace App\Controllers\Api;

use Atom\Voice\PitchCorrectionHarmonizerEngine;

/**
 * VoiceHarmonizer API Controller — Phase 62
 */
class VoiceHarmonizer extends BaseApiController
{
    private static ?PitchCorrectionHarmonizerEngine $engine = null;

    private function getEngine(): PitchCorrectionHarmonizerEngine
    {
        if (self::$engine === null) {
            self::$engine = new PitchCorrectionHarmonizerEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/voice/harmonizer/correct-pitch
     */
    public function correctPitch()
    {
        $json = $this->request->getJSON(true) ?? [];
        $freq = (float) ($json['frequency_hz'] ?? 248.5);
        $scale = $json['scale'] ?? 'c_major';

        $engine = $this->getEngine();
        $quantized = $engine->quantizeToScale($freq, $scale);

        return $this->respondSuccess($quantized, 'Pitch quantized and autotuned');
    }

    /**
     * POST /api/voice/harmonizer/generate-harmonies
     */
    public function generateHarmonies()
    {
        $json = $this->request->getJSON(true) ?? [];
        $baseFreq = (float) ($json['base_frequency_hz'] ?? 245.0);
        $offsets = $json['offsets'] ?? [0, 4, 7, -12];

        $engine = $this->getEngine();
        $harmonies = $engine->generateHarmonies($baseFreq, $offsets);

        return $this->respondSuccess($harmonies, 'Multi-part harmonies generated');
    }

    /**
     * GET /api/voice/harmonizer/scales
     */
    public function scales()
    {
        return $this->respondSuccess([
            'scales' => [
                ['id' => 'c_major', 'name' => 'C Major (Natural Diatonic)', 'notes' => 'C, D, E, F, G, A, B'],
                ['id' => 'a_minor', 'name' => 'A Minor (Heroic Melodic)', 'notes' => 'A, B, C, D, E, F, G'],
                ['id' => 'alien_heroic_245', 'name' => 'Ben 10 Heroic Alien Resonance (245 Hz)', 'notes' => 'Omnitrix Quantized Key'],
            ],
        ], 'Voice harmonizer musical scales');
    }
}
