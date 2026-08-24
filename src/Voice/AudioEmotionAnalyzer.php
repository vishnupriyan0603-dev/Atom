<?php

namespace Atom\Voice;

/**
 * Audio Emotion Analyzer — Phase 34
 *
 * Real-time prosodic audio feature extraction (pitch, energy, speaking rate)
 * to classify speaker emotional tone and provide voice response adaptations.
 */
class AudioEmotionAnalyzer
{
    /**
     * Analyzes acoustic audio features and classifies emotional tone.
     *
     * @param array $features Array with keys: pitch_hz, energy_db, speech_rate_wpm, pitch_variance
     * @return array Classification result with confidence and adaptation advice.
     */
    public function analyze(array $features): array
    {
        $pitch = (float)($features['pitch_hz'] ?? 160.0);
        $energy = (float)($features['energy_db'] ?? -20.0);
        $rate = (float)($features['speech_rate_wpm'] ?? 140.0);
        $pitchVariance = (float)($features['pitch_variance'] ?? 25.0);

        $emotion = 'neutral';
        $confidence = 0.75;
        $toneAdvice = 'balanced_technical';

        if ($energy > -10.0 && $pitch > 220.0 && $rate > 170.0) {
            $emotion = 'urgent';
            $confidence = 0.88;
            $toneAdvice = 'concise_fast_direct';
        } elseif ($pitchVariance > 50.0 && $pitch > 190.0) {
            $emotion = 'curious';
            $confidence = 0.82;
            $toneAdvice = 'engaging_explanatory';
        } elseif ($energy > -12.0 && $pitch < 140.0 && $pitchVariance < 15.0) {
            $emotion = 'frustrated';
            $confidence = 0.85;
            $toneAdvice = 'soothing_empathetic_clear';
        } elseif ($pitch > 200.0 && $pitchVariance > 35.0 && $energy > -18.0) {
            $emotion = 'delighted';
            $confidence = 0.80;
            $toneAdvice = 'cheerful_warm';
        } elseif ($rate < 110.0 && $pitchVariance < 20.0) {
            $emotion = 'hesitant';
            $confidence = 0.78;
            $toneAdvice = 'reassuring_patient';
        }

        return [
            'emotion'      => $emotion,
            'confidence'   => $confidence,
            'features'     => [
                'pitch_hz'        => $pitch,
                'energy_db'       => $energy,
                'speech_rate_wpm' => $rate,
                'pitch_variance'  => $pitchVariance,
            ],
            'adaptation'   => [
                'recommended_tone' => $toneAdvice,
                'speech_rate_mod'  => ($emotion === 'urgent') ? 1.15 : (($emotion === 'hesitant') ? 0.95 : 1.0),
                'pitch_mod'        => ($emotion === 'delighted') ? 1.05 : 1.0,
            ],
        ];
    }
}
