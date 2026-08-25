<?php

namespace App\Controllers\Api;

use Atom\Voice\AudioEmotionClassifierEngine;

/**
 * AudioEmotion API Controller — Phase 68
 */
class AudioEmotion extends BaseApiController
{
    private static ?AudioEmotionClassifierEngine $engine = null;

    private function getEngine(): AudioEmotionClassifierEngine
    {
        if (self::$engine === null) {
            self::$engine = new AudioEmotionClassifierEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/voice/emotion/classify
     */
    public function classify()
    {
        $json = $this->request->getJSON(true) ?? [];
        $engine = $this->getEngine();

        if (isset($json['text'])) {
            $res = $engine->classifyTextIntent($json['text']);
        } else {
            $f0 = (float) ($json['pitch_mean_hz'] ?? 140.0);
            $var = (float) ($json['pitch_variance'] ?? 20.0);
            $energy = (float) ($json['energy_rms'] ?? 0.5);
            $res = $engine->classifyAcoustic($f0, $var, $energy);
        }

        return $this->respondSuccess($res, 'Acoustic emotion classified');
    }

    /**
     * GET /api/voice/emotion/moods
     */
    public function moods()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getSupportedMoods(), 'Supported emotional moods');
    }
}
