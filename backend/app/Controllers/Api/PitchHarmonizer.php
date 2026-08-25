<?php

namespace App\Controllers\Api;

use Atom\Voice\RealTimePitchCorrectorEngine;

/**
 * PitchHarmonizer API Controller — Phase 78
 */
class PitchHarmonizer extends BaseApiController
{
    private static ?RealTimePitchCorrectorEngine $engine = null;

    private function getEngine(): RealTimePitchCorrectorEngine
    {
        if (self::$engine === null) {
            self::$engine = new RealTimePitchCorrectorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/voice/pitch/autotune
     */
    public function autotune()
    {
        $json = $this->request->getJSON(true) ?? [];
        $frames = $json['audio_frames'] ?? [0.1, 0.4, 0.7, 0.3, -0.2, -0.6, 0.1];
        $scale = $json['scale'] ?? 'major';
        $speed = (float) ($json['speed'] ?? 0.8);

        $engine = $this->getEngine();
        $res = $engine->autotunePitch($frames, $scale, $speed);

        return $this->respondSuccess($res, 'Pitch auto-tuned');
    }

    /**
     * POST /api/voice/pitch/harmonize
     */
    public function harmonize()
    {
        $json = $this->request->getJSON(true) ?? [];
        $frames = $json['audio_frames'] ?? [0.2, 0.5, 0.8, -0.3, -0.5];
        $intervals = $json['intervals'] ?? [4, 7];

        $engine = $this->getEngine();
        $res = $engine->synthesizeHarmonies($frames, $intervals);

        return $this->respondSuccess($res, 'Vocal harmonies synthesized');
    }

    /**
     * GET /api/voice/pitch/scales
     */
    public function scales()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getSupportedScales(), 'Supported musical scales');
    }
}
