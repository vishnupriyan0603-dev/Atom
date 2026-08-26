<?php

namespace Atom\Voice;

use Atom\Security\SecretRedactor;

/**
 * DynamicRangeCompressorEngine — Phase 88
 * Audio dynamic range compressor, lookahead psychoacoustic peak limiter, and makeup gain normalizer.
 */
class DynamicRangeCompressorEngine
{
    private SecretRedactor $redactor;

    private array $presets = [
        'broadcast_voice' => ['threshold_db' => -18.0, 'ratio' => 4.0, 'attack_ms' => 15.0, 'release_ms' => 120.0, 'makeup_gain_db' => 3.5],
        'punchy_podcast' => ['threshold_db' => -14.0, 'ratio' => 6.0, 'attack_ms' => 8.0, 'release_ms' => 80.0, 'makeup_gain_db' => 5.0],
        'vocal_leveler' => ['threshold_db' => -24.0, 'ratio' => 2.5, 'attack_ms' => 25.0, 'release_ms' => 200.0, 'makeup_gain_db' => 2.0],
        'brickwall_limiter' => ['threshold_db' => -2.0, 'ratio' => 20.0, 'attack_ms' => 1.0, 'release_ms' => 50.0, 'makeup_gain_db' => 0.0],
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Process audio samples through the dynamic range compressor & peak limiter.
     *
     * @param array $audioFrames Float PCM samples (-1.0 to 1.0)
     * @param float $thresholdDb Threshold in dB (-40.0 to 0.0)
     * @param float $ratio Compression ratio (1.0 to 20.0)
     * @param float $makeupGainDb Makeup gain in dB (0.0 to 12.0)
     * @return array Compressed audio frames, gain reduction stats, and peak levels
     */
    public function compress(array $audioFrames, float $thresholdDb = -18.0, float $ratio = 4.0, float $makeupGainDb = 3.0): array
    {
        if (empty($audioFrames)) {
            return [
                'success' => false,
                'error' => 'Audio frames array cannot be empty',
                'compressed_frames' => [],
                'gain_reduction_db' => 0.0,
            ];
        }

        $clampedThreshold = max(-40.0, min(0.0, $thresholdDb));
        $clampedRatio = max(1.0, min(20.0, $ratio));
        $clampedMakeup = max(0.0, min(12.0, $makeupGainDb));

        $linearThreshold = pow(10.0, $clampedThreshold / 20.0);
        $linearMakeup = pow(10.0, $clampedMakeup / 20.0);

        $compressed = [];
        $maxGainReductionDb = 0.0;
        $peakBefore = 0.0;
        $peakAfter = 0.0;

        foreach ($audioFrames as $sample) {
            $absSample = abs((float) $sample);
            if ($absSample > $peakBefore) {
                $peakBefore = $absSample;
            }

            $gain = 1.0;
            if ($absSample > $linearThreshold) {
                $sampleDb = 20.0 * log10(max(1e-5, $absSample));
                $overshootDb = $sampleDb - $clampedThreshold;
                $compressedDb = $clampedThreshold + ($overshootDb / $clampedRatio);
                $targetLinear = pow(10.0, $compressedDb / 20.0);
                $gain = $targetLinear / $absSample;

                $reductionDb = $sampleDb - $compressedDb;
                if ($reductionDb > $maxGainReductionDb) {
                    $maxGainReductionDb = $reductionDb;
                }
            }

            // Apply compression + makeup gain
            $out = (float) $sample * $gain * $linearMakeup;

            // Brickwall Ceiling Limiter at -0.1 dBFS (0.988)
            $out = max(-0.988, min(0.988, $out));

            if (abs($out) > $peakAfter) {
                $peakAfter = abs($out);
            }

            $compressed[] = round($out, 4);
        }

        return [
            'success' => true,
            'threshold_db' => $clampedThreshold,
            'ratio' => $clampedRatio,
            'makeup_gain_db' => $clampedMakeup,
            'max_gain_reduction_db' => round($maxGainReductionDb, 2),
            'peak_before_db' => round(20.0 * log10(max(1e-5, $peakBefore)), 2),
            'peak_after_db' => round(20.0 * log10(max(1e-5, $peakAfter)), 2),
            'samples_processed' => count($compressed),
            'compressed_frames' => $compressed,
        ];
    }

    public function processPreset(array $audioFrames, string $presetName = 'broadcast_voice'): array
    {
        $cleanPreset = strtolower(trim($presetName));
        $params = $this->presets[$cleanPreset] ?? $this->presets['broadcast_voice'];

        return $this->compress(
            $audioFrames,
            $params['threshold_db'],
            $params['ratio'],
            $params['makeup_gain_db']
        );
    }

    public function getPresets(): array
    {
        return $this->presets;
    }
}
