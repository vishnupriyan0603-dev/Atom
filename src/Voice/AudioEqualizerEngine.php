<?php

namespace Atom\Voice;

/**
 * Audio Equalizer Engine — DSP Audio Processing & Filter Chain Subsystem
 *
 * Implements a deterministic, high-fidelity 10-Band Parametric/Graphic Audio Equalizer
 * with Biquad coefficient calculation, frequency response curve synthesis,
 * curated acoustic presets, and safe numerical clamping.
 */
class AudioEqualizerEngine
{
    public const BANDS = [32, 64, 125, 250, 500, 1000, 2000, 4000, 8000, 16000];
    public const MIN_GAIN_DB = -12.0;
    public const MAX_GAIN_DB = 12.0;
    public const DEFAULT_Q = 1.414; // Standard 1-octave bandwidth Q

    public const PRESETS = [
        'FLAT' => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
        'BASS_BOOST' => [6.0, 5.0, 4.0, 2.0, 0.5, 0.0, 0.0, 0.0, 0.0, 0.0],
        'VOCAL_ENHANCE' => [-2.0, -1.0, 0.0, 2.0, 4.0, 4.5, 3.5, 2.0, 0.5, 0.0],
        'TREBLE_BOOST' => [0.0, 0.0, 0.0, 0.0, 0.5, 1.5, 3.0, 5.0, 6.0, 7.0],
        'ACOUSTIC' => [3.5, 3.0, 2.0, 1.0, 1.5, 2.0, 3.0, 3.5, 3.0, 2.0],
        'ELECTRONIC' => [5.0, 4.5, 2.0, 0.0, -1.5, 1.5, 0.5, 2.0, 4.0, 5.0],
        'ROCK' => [4.5, 3.5, 1.5, -0.5, -1.5, 0.5, 2.5, 4.0, 4.5, 5.0],
        'SPEECH_CLARITY' => [-4.0, -2.0, 0.0, 2.0, 3.5, 4.0, 3.0, 1.5, 0.0, -2.0],
        'NOISE_REDUCTION' => [-3.0, -1.5, 0.0, 0.0, 0.0, 0.0, 0.0, -2.0, -4.5, -7.0],
        'PODCAST' => [-3.0, 0.0, 1.5, 3.0, 3.5, 3.0, 2.0, 1.0, -0.5, -2.5],
    ];

    private bool $enabled = true;
    private float $preampDb = 0.0;
    private array $bandGains = [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    private string $activePreset = 'FLAT';
    private bool $lowCutEnabled = false;
    private float $lowCutFreq = 80.0;
    private bool $highCutEnabled = false;
    private float $highCutFreq = 12000.0;

    public function __construct(array $initialGains = [], float $preampDb = 0.0)
    {
        if (!empty($initialGains)) {
            $this->setBands($initialGains);
        }
        $this->setPreamp($preampDb);
    }

    /**
     * Sanitizes and clamps numerical gain to [-12, +12] dB.
     */
    public static function sanitizeGain(mixed $gain): float
    {
        if (!is_numeric($gain) || is_nan((float)$gain) || is_infinite((float)$gain)) {
            return 0.0;
        }
        $val = (float)$gain;
        return max(self::MIN_GAIN_DB, min(self::MAX_GAIN_DB, round($val, 2)));
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getPreamp(): float
    {
        return $this->preampDb;
    }

    public function setPreamp(mixed $preampDb): self
    {
        $this->preampDb = self::sanitizeGain($preampDb);
        return $this;
    }

    public function getBandGain(int $index): float
    {
        if ($index < 0 || $index >= count(self::BANDS)) {
            return 0.0;
        }
        return $this->bandGains[$index] ?? 0.0;
    }

    public function setBandGain(int $index, mixed $gainDb): self
    {
        if ($index >= 0 && $index < count(self::BANDS)) {
            $this->bandGains[$index] = self::sanitizeGain($gainDb);
            $this->activePreset = $this->detectMatchingPreset();
        }
        return $this;
    }

    public function getBands(): array
    {
        return $this->bandGains;
    }

    /**
     * Sets all 10 band gains safely with validation and length protection.
     */
    public function setBands(array $gains): self
    {
        $clean = [];
        for ($i = 0; $i < count(self::BANDS); $i++) {
            $val = $gains[$i] ?? ($gains[(string)$i] ?? 0.0);
            $clean[$i] = self::sanitizeGain($val);
        }
        $this->bandGains = $clean;
        $this->activePreset = $this->detectMatchingPreset();
        return $this;
    }

    public function getActivePreset(): string
    {
        return $this->activePreset;
    }

    /**
     * Applies a named preset deterministically.
     */
    public function applyPreset(string $presetName): bool
    {
        $normalized = strtoupper(trim($presetName));
        if (isset(self::PRESETS[$normalized])) {
            $this->bandGains = self::PRESETS[$normalized];
            $this->activePreset = $normalized;
            return true;
        }
        return false;
    }

    public function getPresets(): array
    {
        return self::PRESETS;
    }

    public function reset(): self
    {
        $this->bandGains = self::PRESETS['FLAT'];
        $this->preampDb = 0.0;
        $this->activePreset = 'FLAT';
        $this->enabled = true;
        $this->lowCutEnabled = false;
        $this->highCutEnabled = false;
        return $this;
    }

    public function setLowCut(bool $enabled, float $freq = 80.0): self
    {
        $this->lowCutEnabled = $enabled;
        $this->lowCutFreq = max(20.0, min(1000.0, $freq));
        return $this;
    }

    public function isLowCutEnabled(): bool
    {
        return $this->lowCutEnabled;
    }

    public function getLowCutFreq(): float
    {
        return $this->lowCutFreq;
    }

    public function setHighCut(bool $enabled, float $freq = 12000.0): self
    {
        $this->highCutEnabled = $enabled;
        $this->highCutFreq = max(1000.0, min(20000.0, $freq));
        return $this;
    }

    public function isHighCutEnabled(): bool
    {
        return $this->highCutEnabled;
    }

    public function getHighCutFreq(): float
    {
        return $this->highCutFreq;
    }

    /**
     * Calculates digital biquad peaking EQ filter coefficients using the Audio EQ Cookbook formula.
     * H(s) = (s^2 + s*(A/Q) + 1) / (s^2 + s/(A*Q) + 1)
     */
    public function computeBiquadCoefficients(int $bandIndex, float $sampleRate = 48000.0): array
    {
        if ($bandIndex < 0 || $bandIndex >= count(self::BANDS)) {
            return ['b0' => 1.0, 'b1' => 0.0, 'b2' => 0.0, 'a0' => 1.0, 'a1' => 0.0, 'a2' => 0.0];
        }

        $f0 = (float)self::BANDS[$bandIndex];
        $gainDb = $this->enabled ? $this->bandGains[$bandIndex] : 0.0;
        $A = pow(10.0, $gainDb / 40.0);
        $w0 = 2.0 * M_PI * ($f0 / $sampleRate);
        $alpha = sin($w0) / (2.0 * self::DEFAULT_Q);

        $b0 = 1.0 + ($alpha * $A);
        $b1 = -2.0 * cos($w0);
        $b2 = 1.0 - ($alpha * $A);
        $a0 = 1.0 + ($alpha / $A);
        $a1 = -2.0 * cos($w0);
        $a2 = 1.0 - ($alpha / $A);

        // Normalize by a0
        return [
            'frequency' => $f0,
            'gain_db'   => $gainDb,
            'q'         => self::DEFAULT_Q,
            'b0'        => round($b0 / $a0, 6),
            'b1'        => round($b1 / $a0, 6),
            'b2'        => round($b2 / $a0, 6),
            'a1'        => round($a1 / $a0, 6),
            'a2'        => round($a2 / $a0, 6),
        ];
    }

    /**
     * Computes the composite frequency response curve magnitude across logarithmically spaced points.
     */
    public function computeFrequencyResponse(int $points = 80): array
    {
        $curve = [];
        $minLog = log10(20.0);
        $maxLog = log10(20000.0);
        $step = ($maxLog - $minLog) / max(1, $points - 1);

        for ($i = 0; $i < $points; $i++) {
            $freq = pow(10.0, $minLog + ($i * $step));
            $totalGain = $this->enabled ? $this->preampDb : 0.0;

            if ($this->enabled) {
                // Sum peaking band contributions with Gaussian-shaped octave falloff
                foreach (self::BANDS as $idx => $centerFreq) {
                    $bandGain = $this->bandGains[$idx];
                    if (abs($bandGain) > 0.01) {
                        $octaveDist = log($freq / $centerFreq, 2);
                        // Standard Gaussian approximation of 1-octave band filter response
                        $weight = exp(-0.5 * pow($octaveDist / 0.5, 2));
                        $totalGain += ($bandGain * $weight);
                    }
                }

                // Low-cut (highpass) attenuation
                if ($this->lowCutEnabled && $freq < $this->lowCutFreq) {
                    $octavesBelow = log($this->lowCutFreq / max(1.0, $freq), 2);
                    $totalGain -= ($octavesBelow * 12.0); // 12dB/octave slope
                }

                // High-cut (lowpass) attenuation
                if ($this->highCutEnabled && $freq > $this->highCutFreq) {
                    $octavesAbove = log($freq / $this->highCutFreq, 2);
                    $totalGain -= ($octavesAbove * 12.0); // 12dB/octave slope
                }
            }

            $curve[] = [
                'freq' => round($freq, 1),
                'gain' => round(max(-36.0, min(24.0, $totalGain)), 2),
            ];
        }

        return $curve;
    }

    /**
     * Serializes complete immutable equalizer state.
     */
    public function getState(): array
    {
        return [
            'enabled'        => $this->enabled,
            'preamp_db'      => $this->preampDb,
            'bands'          => array_combine(self::BANDS, $this->bandGains),
            'band_gains'     => $this->bandGains,
            'preset'         => $this->activePreset,
            'low_cut'        => [
                'enabled'   => $this->lowCutEnabled,
                'frequency' => $this->lowCutFreq,
            ],
            'high_cut'       => [
                'enabled'   => $this->highCutEnabled,
                'frequency' => $this->highCutFreq,
            ],
            'timestamp'      => microtime(true),
        ];
    }

    private function detectMatchingPreset(): string
    {
        foreach (self::PRESETS as $name => $gains) {
            $match = true;
            for ($i = 0; $i < count(self::BANDS); $i++) {
                if (abs($this->bandGains[$i] - $gains[$i]) > 0.05) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $name;
            }
        }
        return 'CUSTOM';
    }
}
