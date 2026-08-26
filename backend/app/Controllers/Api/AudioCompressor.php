<?php

namespace App\Controllers\Api;

use Atom\Voice\DynamicRangeCompressorEngine;

/**
 * AudioCompressor API Controller — Phase 88
 */
class AudioCompressor extends BaseApiController
{
    private static ?DynamicRangeCompressorEngine $engine = null;

    private function getEngine(): DynamicRangeCompressorEngine
    {
        if (self::$engine === null) {
            self::$engine = new DynamicRangeCompressorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/voice/compressor/process
     */
    public function process()
    {
        $json = $this->request->getJSON(true) ?? [];
        $frames = $json['audio_frames'] ?? [0.1, 0.4, 0.85, 0.95, -0.6, -0.9, 0.2];
        $preset = $json['preset'] ?? null;

        $engine = $this->getEngine();

        if ($preset !== null) {
            $res = $engine->processPreset($frames, $preset);
        } else {
            $thresh = (float) ($json['threshold_db'] ?? -18.0);
            $ratio = (float) ($json['ratio'] ?? 4.0);
            $makeup = (float) ($json['makeup_gain_db'] ?? 3.0);
            $res = $engine->compress($frames, $thresh, $ratio, $makeup);
        }

        return $this->respondSuccess($res, 'Audio compressed and peak-limited');
    }

    /**
     * GET /api/voice/compressor/presets
     */
    public function presets()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getPresets(), 'Compressor studio presets');
    }
}
