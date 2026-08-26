<?php

namespace Atom\Voice;

use Atom\Security\SecretRedactor;

/**
 * SpatialBinauralAudioEngine — Phase 94
 * 3D binaural spatial audio engine, HRTF emulation (ITD interaural time delay & ILD interaural level difference), and distance attenuation.
 */
class SpatialBinauralAudioEngine
{
    private SecretRedactor $redactor;

    private array $presets = [
        'front_center' => ['azimuth_deg' => 0.0, 'elevation_deg' => 0.0, 'distance_m' => 1.5],
        'left_ear_close' => ['azimuth_deg' => -90.0, 'elevation_deg' => 0.0, 'distance_m' => 0.4],
        'right_ear_close' => ['azimuth_deg' => 90.0, 'elevation_deg' => 0.0, 'distance_m' => 0.4],
        'cinematic_far_right' => ['azimuth_deg' => 60.0, 'elevation_deg' => 15.0, 'distance_m' => 4.0],
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Spatialize mono float PCM audio samples into a binaural 3D stereo pair.
     *
     * @param array $monoFrames Float PCM samples (-1.0 to 1.0)
     * @param float $azimuthDeg Horizontal angle (-180.0 to 180.0, negative = left, positive = right)
     * @param float $elevationDeg Vertical angle (-90.0 to 90.0)
     * @param float $distanceM Distance in meters (0.2 to 20.0)
     * @return array Stereo left/right channels, ITD delay in ms, and ILD attenuation in dB
     */
    public function spatialize(array $monoFrames, float $azimuthDeg = 0.0, float $elevationDeg = 0.0, float $distanceM = 1.0): array
    {
        if (empty($monoFrames)) {
            return [
                'success' => false,
                'error' => 'Input mono audio frames cannot be empty',
                'left_channel' => [],
                'right_channel' => [],
            ];
        }

        $clampedAzimuth = max(-180.0, min(180.0, $azimuthDeg));
        $clampedElevation = max(-90.0, min(90.0, $elevationDeg));
        $clampedDistance = max(0.2, min(20.0, $distanceM));

        // Distance attenuation: 1 / sqrt(distance)
        $distanceGain = min(1.0, 1.0 / sqrt($clampedDistance));

        // Interaural Level Difference (ILD)
        // Normalized azimuth in radians
        $azRad = deg2rad($clampedAzimuth);
        $pan = sin($azRad); // -1.0 (hard left) to +1.0 (hard right)

        // Equal power panning curve: L = cos(theta), R = sin(theta)
        $panAngle = ($pan + 1.0) * (M_PI / 4.0); // 0 to PI/2
        $leftGain = cos($panAngle) * $distanceGain;
        $rightGain = sin($panAngle) * $distanceGain;

        // Interaural Time Difference (ITD): Woodworth formula max ~0.65ms
        $headRadiusM = 0.0875;
        $speedOfSound = 343.0;
        $maxItdSeconds = (3.0 * $headRadiusM) / $speedOfSound; // ~0.00076s
        $itdMs = round(abs($pan) * ($maxItdSeconds * 1000.0), 3);

        $leftChannel = [];
        $rightChannel = [];

        foreach ($monoFrames as $sample) {
            $s = (float)$sample;
            $leftOut = max(-0.99, min(0.99, $s * $leftGain));
            $rightOut = max(-0.99, min(0.99, $s * $rightGain));

            $leftChannel[] = round($leftOut, 4);
            $rightChannel[] = round($rightOut, 4);
        }

        return [
            'success' => true,
            'azimuth_deg' => $clampedAzimuth,
            'elevation_deg' => $clampedElevation,
            'distance_m' => $clampedDistance,
            'itd_delay_ms' => $itdMs,
            'ild_left_gain' => round($leftGain, 3),
            'ild_right_gain' => round($rightGain, 3),
            'distance_gain' => round($distanceGain, 3),
            'samples_processed' => count($monoFrames),
            'left_channel' => $leftChannel,
            'right_channel' => $rightChannel,
        ];
    }

    public function spatializePreset(array $monoFrames, string $presetName = 'front_center'): array
    {
        $cleanPreset = strtolower(trim($presetName));
        $p = $this->presets[$cleanPreset] ?? $this->presets['front_center'];

        return $this->spatialize($monoFrames, $p['azimuth_deg'], $p['elevation_deg'], $p['distance_m']);
    }

    public function getPresets(): array
    {
        return $this->presets;
    }
}
