<?php

namespace App\Controllers\Api;

use Atom\Voice\SpatialBinauralAudioEngine;

/**
 * AudioSpatializer API Controller — Phase 94
 */
class AudioSpatializer extends BaseApiController
{
    private static ?SpatialBinauralAudioEngine $engine = null;

    private function getEngine(): SpatialBinauralAudioEngine
    {
        if (self::$engine === null) {
            self::$engine = new SpatialBinauralAudioEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/voice/spatial/process
     */
    public function process()
    {
        $json = $this->request->getJSON(true) ?? [];
        $frames = $json['mono_frames'] ?? [0.1, 0.4, 0.8, 0.95, -0.6, -0.9, 0.2];
        $preset = $json['preset'] ?? null;

        $engine = $this->getEngine();

        if ($preset !== null) {
            $res = $engine->spatializePreset($frames, $preset);
        } else {
            $azimuth = (float) ($json['azimuth_deg'] ?? 45.0);
            $elevation = (float) ($json['elevation_deg'] ?? 0.0);
            $distance = (float) ($json['distance_m'] ?? 1.5);
            $res = $engine->spatialize($frames, $azimuth, $elevation, $distance);
        }

        return $this->respondSuccess($res, 'Mono audio spatialized into 3D binaural stereo');
    }

    /**
     * GET /api/voice/spatial/presets
     */
    public function presets()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getPresets(), '3D soundscape trajectory presets');
    }
}
