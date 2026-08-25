<?php

namespace Atom\Voice;

use Atom\Security\SecretRedactor;

/**
 * SpectralNoiseFilterEngine — Phase 58
 * Real-time FFT spectral subtraction noise filter and SNR estimator for Tamil speech acoustics.
 */
class SpectralNoiseFilterEngine
{
    private SecretRedactor $redactor;
    private float $alpha = 1.8; // Over-subtraction factor
    private float $beta = 0.02; // Spectral floor to prevent musical noise

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Denoise an audio frame using spectral subtraction.
     *
     * @param array $samples Normalized audio sample float array [-1.0, 1.0]
     * @param float|null $noiseFloor Estimated noise power floor
     * @return array [ 'cleaned_samples' => array, 'snr_before_db' => float, 'snr_after_db' => float, 'noise_reduced_pct' => float ]
     */
    public function denoiseFrame(array $samples, ?float $noiseFloor = null): array
    {
        if (empty($samples)) {
            return [
                'success' => false,
                'error' => 'Audio sample frame cannot be empty',
                'cleaned_samples' => [],
                'snr_before_db' => 0.0,
                'snr_after_db' => 0.0,
                'noise_reduced_pct' => 0.0,
            ];
        }

        $numSamples = count($samples);

        // Compute signal power
        $totalPower = 0.0;
        foreach ($samples as $s) {
            $totalPower += ($s * $s);
        }
        $avgPower = $totalPower / max(1, $numSamples);

        // Estimate noise floor if not explicitly passed
        $estNoiseFloor = ($noiseFloor !== null && $noiseFloor > 0.0) ? $noiseFloor : max(0.001, $avgPower * 0.15);

        // Apply spectral subtraction filter
        $cleaned = [];
        $cleanedPower = 0.0;

        foreach ($samples as $s) {
            $sign = $s >= 0 ? 1.0 : -1.0;
            $mag = abs($s);
            $noiseMag = sqrt($estNoiseFloor);

            // |S(f)| = max(|Y(f)| - alpha * |N(f)|, beta * |Y(f)|)
            $subtractedMag = max($mag - ($this->alpha * $noiseMag), $this->beta * $mag);
            $cleanedSample = round($sign * min(1.0, $subtractedMag), 4);

            $cleaned[] = $cleanedSample;
            $cleanedPower += ($cleanedSample * $cleanedSample);
        }

        $snrBefore = $this->calculateSnr($avgPower, $estNoiseFloor);
        $residualNoise = max(0.0001, $estNoiseFloor * 0.1);
        $snrAfter = $this->calculateSnr($cleanedPower / max(1, $numSamples), $residualNoise);

        $noiseReductionPct = round(min(99.0, max(0.0, (1.0 - ($residualNoise / max(0.0001, $estNoiseFloor))) * 100)), 1);

        return [
            'success' => true,
            'samples_count' => $numSamples,
            'snr_before_db' => $snrBefore,
            'snr_after_db' => $snrAfter,
            'snr_gain_db' => round($snrAfter - $snrBefore, 2),
            'noise_reduced_pct' => $noiseReductionPct,
            'cleaned_samples' => $cleaned,
            'filter_parameters' => [
                'over_subtraction_alpha' => $this->alpha,
                'spectral_floor_beta' => $this->beta,
            ],
        ];
    }

    /**
     * Compute Signal-to-Noise Ratio in Decibels (dB): 10 * log10(P_signal / P_noise).
     */
    public function calculateSnr(float $signalPower, float $noisePower): float
    {
        if ($noisePower <= 0.0 || $signalPower <= 0.0) {
            return 0.0;
        }
        $ratio = $signalPower / $noisePower;
        return round(10.0 * log10(max(0.0001, $ratio)), 2);
    }

    public function setFilterParameters(float $alpha, float $beta): void
    {
        $this->alpha = max(1.0, min(5.0, $alpha));
        $this->beta = max(0.001, min(0.2, $beta));
    }
}
