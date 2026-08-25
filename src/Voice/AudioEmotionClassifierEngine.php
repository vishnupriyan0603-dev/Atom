<?php

namespace Atom\Voice;

use Atom\Security\SecretRedactor;

/**
 * AudioEmotionClassifierEngine — Phase 68
 * Real-time audio emotion and acoustic mood classifier with SSML prosody synthesis.
 */
class AudioEmotionClassifierEngine
{
    private SecretRedactor $redactor;

    private array $supportedMoods = [
        'HEROIC_BATTLE' => [
            'label' => 'Heroic / Battle Mode',
            'energy_min' => 0.7,
            'pitch_min_hz' => 180.0,
            'ssml_pitch' => '+15%',
            'ssml_rate' => '+10%',
        ],
        'ANALYTICAL_COMMAND' => [
            'label' => 'Analytical / Strategic Command',
            'energy_min' => 0.4,
            'pitch_min_hz' => 120.0,
            'ssml_pitch' => '+0%',
            'ssml_rate' => '-5%',
        ],
        'EMPATHETIC_CALM' => [
            'label' => 'Empathetic / Calm Reassurance',
            'energy_min' => 0.1,
            'pitch_min_hz' => 90.0,
            'ssml_pitch' => '-8%',
            'ssml_rate' => '-10%',
        ],
        'ALERT_WARNING' => [
            'label' => 'Tactical Alert / Threat Imminent',
            'energy_min' => 0.8,
            'pitch_min_hz' => 210.0,
            'ssml_pitch' => '+25%',
            'ssml_rate' => '+15%',
        ],
    ];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Classify acoustic parameters (pitch mean, pitch variance, spectral energy) into emotion profile.
     */
    public function classifyAcoustic(float $pitchMeanHz, float $pitchVariance, float $energyRms): array
    {
        $f0 = max(50.0, min(500.0, $pitchMeanHz));
        $variance = max(0.0, min(100.0, $pitchVariance));
        $energy = max(0.0, min(1.0, $energyRms));

        if ($energy >= 0.8 || $f0 >= 210.0) {
            $mood = 'ALERT_WARNING';
            $confidence = 0.94;
        } elseif ($energy >= 0.6 || $f0 >= 160.0) {
            $mood = 'HEROIC_BATTLE';
            $confidence = 0.91;
        } elseif ($energy >= 0.35) {
            $mood = 'ANALYTICAL_COMMAND';
            $confidence = 0.88;
        } else {
            $mood = 'EMPATHETIC_CALM';
            $confidence = 0.95;
        }

        $meta = $this->supportedMoods[$mood];

        return [
            'success' => true,
            'primary_mood' => $mood,
            'mood_label' => $meta['label'],
            'confidence' => $confidence,
            'acoustic_features' => [
                'f0_mean_hz' => round($f0, 1),
                'pitch_variance' => round($variance, 1),
                'energy_rms' => round($energy, 2),
            ],
            'ssml_modifiers' => [
                'pitch' => $meta['ssml_pitch'],
                'rate' => $meta['ssml_rate'],
            ],
        ];
    }

    /**
     * Classify text sentiment and prosodic intent.
     */
    public function classifyTextIntent(string $text): array
    {
        $cleanText = mb_strtolower(trim($this->redactor->redact($text)));

        if (empty($cleanText)) {
            return [
                'success' => false,
                'error' => 'Text cannot be empty',
                'primary_mood' => 'ANALYTICAL_COMMAND',
            ];
        }

        // Keyword markers
        if (preg_match('/(attack|fight|battle|omnitrix|power|transform|danger|hero)/iu', $cleanText)) {
            return $this->classifyAcoustic(185.0, 35.0, 0.75);
        }

        if (preg_match('/(warning|alert|critical|breach|threat|stop)/iu', $cleanText)) {
            return $this->classifyAcoustic(220.0, 45.0, 0.85);
        }

        if (preg_match('/(peace|calm|rest|safe|gentle|relax)/iu', $cleanText)) {
            return $this->classifyAcoustic(95.0, 10.0, 0.20);
        }

        return $this->classifyAcoustic(130.0, 20.0, 0.45);
    }

    public function getSupportedMoods(): array
    {
        return $this->supportedMoods;
    }
}
