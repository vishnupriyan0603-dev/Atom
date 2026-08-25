<?php

namespace App\Controllers\Api;

use Atom\Voice\AudioStemSeparatorEngine;

/**
 * AudioStems API Controller — Phase 73
 */
class AudioStems extends BaseApiController
{
    private static ?AudioStemSeparatorEngine $engine = null;

    private function getEngine(): AudioStemSeparatorEngine
    {
        if (self::$engine === null) {
            self::$engine = new AudioStemSeparatorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/voice/stems/separate
     */
    public function separate()
    {
        $json = $this->request->getJSON(true) ?? [];
        $frames = $json['audio_frames'] ?? [0.1, 0.4, 0.8, 0.6, -0.2, -0.7, -0.5, 0.3];
        $strength = (float) ($json['vocal_isolation_strength'] ?? 0.85);

        $engine = $this->getEngine();
        $res = $engine->separateStems($frames, $strength);

        return $this->respondSuccess($res, 'Audio stems separated');
    }

    /**
     * POST /api/voice/stems/mix
     */
    public function mix()
    {
        $json = $this->request->getJSON(true) ?? [];
        $vocal = $json['vocal_stem'] ?? [0.1, 0.4, 0.8];
        $inst = $json['instrumental_stem'] ?? [0.05, 0.1, 0.2];
        $vGain = (float) ($json['vocal_gain'] ?? 1.0);
        $iGain = (float) ($json['instrumental_gain'] ?? 0.5);

        $engine = $this->getEngine();
        $res = $engine->mixStems($vocal, $inst, $vGain, $iGain);

        return $this->respondSuccess($res, 'Stems mixed successfully');
    }

    /**
     * GET /api/voice/stems/bands
     */
    public function bands()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getFrequencyBands(), 'Frequency band matrix');
    }
}
