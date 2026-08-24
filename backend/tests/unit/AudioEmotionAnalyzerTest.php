<?php

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioEmotionAnalyzer;

/**
 * Phase 34 — AudioEmotionAnalyzer unit tests (5 tests).
 */
class AudioEmotionAnalyzerTest extends TestCase
{
    private AudioEmotionAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new AudioEmotionAnalyzer();
    }

    public function testClassifyUrgentEmotionTone(): void
    {
        $features = [
            'pitch_hz'        => 240.0,
            'energy_db'       => -6.0,
            'speech_rate_wpm' => 190.0,
            'pitch_variance'  => 30.0,
        ];

        $res = $this->analyzer->analyze($features);

        $this->assertSame('urgent', $res['emotion']);
        $this->assertGreaterThan(0.8, $res['confidence']);
        $this->assertSame('concise_fast_direct', $res['adaptation']['recommended_tone']);
        $this->assertGreaterThan(1.0, $res['adaptation']['speech_rate_mod']);
    }

    public function testClassifyCuriousEmotionTone(): void
    {
        $features = [
            'pitch_hz'        => 210.0,
            'energy_db'       => -20.0,
            'speech_rate_wpm' => 140.0,
            'pitch_variance'  => 60.0, // High pitch variance indicates questioning/curious
        ];

        $res = $this->analyzer->analyze($features);

        $this->assertSame('curious', $res['emotion']);
        $this->assertSame('engaging_explanatory', $res['adaptation']['recommended_tone']);
    }

    public function testClassifyFrustratedEmotionTone(): void
    {
        $features = [
            'pitch_hz'        => 130.0,
            'energy_db'       => -10.0,
            'speech_rate_wpm' => 135.0,
            'pitch_variance'  => 10.0,
        ];

        $res = $this->analyzer->analyze($features);

        $this->assertSame('frustrated', $res['emotion']);
        $this->assertSame('soothing_empathetic_clear', $res['adaptation']['recommended_tone']);
    }

    public function testClassifyNeutralBaselineTone(): void
    {
        $features = [
            'pitch_hz'        => 160.0,
            'energy_db'       => -22.0,
            'speech_rate_wpm' => 140.0,
            'pitch_variance'  => 22.0,
        ];

        $res = $this->analyzer->analyze($features);

        $this->assertSame('neutral', $res['emotion']);
        $this->assertSame(1.0, $res['adaptation']['speech_rate_mod']);
    }

    public function testDefaultHandlingWhenFeaturesEmpty(): void
    {
        $res = $this->analyzer->analyze([]);

        $this->assertSame('neutral', $res['emotion']);
        $this->assertArrayHasKey('pitch_hz', $res['features']);
    }
}
