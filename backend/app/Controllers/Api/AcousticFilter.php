<?php

namespace App\Controllers\Api;

use Atom\Voice\SpectralNoiseFilterEngine;

/**
 * AcousticFilter API Controller — Phase 58
 */
class AcousticFilter extends BaseApiController
{
    private static ?SpectralNoiseFilterEngine $engine = null;

    private function getEngine(): SpectralNoiseFilterEngine
    {
        if (self::$engine === null) {
            self::$engine = new SpectralNoiseFilterEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/voice/filter/denoise
     */
    public function denoise()
    {
        $json = $this->request->getJSON(true) ?? [];
        $samples = $json['samples'] ?? [];
        $alpha = (float) ($json['alpha'] ?? 1.8);
        $beta = (float) ($json['beta'] ?? 0.02);

        if (empty($samples)) {
            // Generate simulated audio sample with noise for demo
            $samples = [];
            for ($i = 0; $i < 64; $i++) {
                $signal = sin($i * 0.2);
                $noise = (mt_rand(-20, 20) / 100.0);
                $samples[] = round($signal + $noise, 4);
            }
        }

        $engine = $this->getEngine();
        $engine->setFilterParameters($alpha, $beta);
        $result = $engine->denoiseFrame($samples);

        return $this->respondSuccess($result, 'Audio sample denoised using spectral subtraction');
    }

    /**
     * GET /api/voice/filter/presets
     */
    public function presets()
    {
        return $this->respondSuccess([
            'presets' => [
                ['id' => 'speech_tamil_clean', 'name' => 'Tamil Speech Studio Clarity', 'alpha' => 1.8, 'beta' => 0.02, 'description' => 'Optimized for Ben 10 formant resonance and Tamil phonetics'],
                ['id' => 'aggressive_denoise', 'name' => 'Aggressive Background Hum Removal', 'alpha' => 3.0, 'beta' => 0.01, 'description' => 'Maximum attenuation for noisy microphone environments'],
                ['id' => 'mild_acoustic_polish', 'name' => 'Mild Acoustic Polish', 'alpha' => 1.2, 'beta' => 0.05, 'description' => 'Preserves natural acoustic room harmonics'],
            ],
            'algorithm' => 'Spectral Subtraction with Adaptive Noise Floor Estimation',
        ], 'Acoustic filter presets');
    }
}
