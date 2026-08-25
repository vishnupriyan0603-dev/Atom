<?php

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioEqualizerEngine;

/**
 * AudioEqualizerEngine unit test suite (10 tests).
 */
class AudioEqualizerEngineTest extends TestCase
{
    private AudioEqualizerEngine $eq;

    protected function setUp(): void
    {
        $this->eq = new AudioEqualizerEngine();
    }

    public function testDefaultInitializationIsFlatAndEnabled(): void
    {
        $this->assertTrue($this->eq->isEnabled());
        $this->assertSame(0.0, $this->eq->getPreamp());
        $this->assertSame('FLAT', $this->eq->getActivePreset());
        $bands = $this->eq->getBands();
        $this->assertCount(10, $bands);
        foreach ($bands as $gain) {
            $this->assertSame(0.0, $gain);
        }
    }

    public function testSetBandGainClampsToBounds(): void
    {
        $this->eq->setBandGain(0, 25.0);
        $this->assertSame(12.0, $this->eq->getBandGain(0));

        $this->eq->setBandGain(1, -30.0);
        $this->assertSame(-12.0, $this->eq->getBandGain(1));
    }

    public function testNaNAndInvalidGainsDefaultToZero(): void
    {
        $this->eq->setBandGain(2, NAN);
        $this->assertSame(0.0, $this->eq->getBandGain(2));

        $this->eq->setBandGain(3, INF);
        $this->assertSame(0.0, $this->eq->getBandGain(3));

        $this->eq->setBandGain(4, "not-a-number");
        $this->assertSame(0.0, $this->eq->getBandGain(4));
    }

    public function testSetBandsHandlesSparseOrOversizedArrays(): void
    {
        $this->eq->setBands([3.0, 4.5]);
        $bands = $this->eq->getBands();
        $this->assertCount(10, $bands);
        $this->assertSame(3.0, $bands[0]);
        $this->assertSame(4.5, $bands[1]);
        $this->assertSame(0.0, $bands[2]);
    }

    public function testApplyPresetUpdatesAllBandsAndActivePresetName(): void
    {
        $applied = $this->eq->applyPreset('VOCAL_ENHANCE');
        $this->assertTrue($applied);
        $this->assertSame('VOCAL_ENHANCE', $this->eq->getActivePreset());

        $expected = AudioEqualizerEngine::PRESETS['VOCAL_ENHANCE'];
        $this->assertSame($expected, $this->eq->getBands());

        $invalid = $this->eq->applyPreset('NON_EXISTENT_PRESET');
        $this->assertFalse($invalid);
    }

    public function testCustomGainChangesPresetToCustom(): void
    {
        $this->eq->applyPreset('BASS_BOOST');
        $this->assertSame('BASS_BOOST', $this->eq->getActivePreset());

        $this->eq->setBandGain(0, 11.5);
        $this->assertSame('CUSTOM', $this->eq->getActivePreset());
    }

    public function testPreampGainClamping(): void
    {
        $this->eq->setPreamp(15.0);
        $this->assertSame(12.0, $this->eq->getPreamp());

        $this->eq->setPreamp(-18.0);
        $this->assertSame(-12.0, $this->eq->getPreamp());

        $this->eq->setPreamp(4.5);
        $this->assertSame(4.5, $this->eq->getPreamp());
    }

    public function testLowCutAndHighCutFilters(): void
    {
        $this->assertFalse($this->eq->isLowCutEnabled());
        $this->assertFalse($this->eq->isHighCutEnabled());

        $this->eq->setLowCut(true, 100.0);
        $this->assertTrue($this->eq->isLowCutEnabled());
        $this->assertSame(100.0, $this->eq->getLowCutFreq());

        $this->eq->setHighCut(true, 10000.0);
        $this->assertTrue($this->eq->isHighCutEnabled());
        $this->assertSame(10000.0, $this->eq->getHighCutFreq());
    }

    public function testComputeBiquadCoefficientsFormulas(): void
    {
        $this->eq->setBandGain(5, 6.0); // 1000 Hz at +6dB
        $coeff = $this->eq->computeBiquadCoefficients(5, 48000.0);

        $this->assertSame(1000.0, $coeff['frequency']);
        $this->assertSame(6.0, $coeff['gain_db']);
        $this->assertArrayHasKey('b0', $coeff);
        $this->assertArrayHasKey('b1', $coeff);
        $this->assertArrayHasKey('b2', $coeff);
        $this->assertArrayHasKey('a1', $coeff);
        $this->assertArrayHasKey('a2', $coeff);
    }

    public function testComputeFrequencyResponseCurveMagnitude(): void
    {
        $this->eq->applyPreset('BASS_BOOST');
        $curve = $this->eq->computeFrequencyResponse(50);

        $this->assertCount(50, $curve);
        $this->assertArrayHasKey('freq', $curve[0]);
        $this->assertArrayHasKey('gain', $curve[0]);

        // Bass frequencies should have higher gain than high frequencies in BASS_BOOST
        $lowFreqGain = $curve[5]['gain'];
        $highFreqGain = $curve[45]['gain'];
        $this->assertGreaterThan($highFreqGain, $lowFreqGain);
    }
}
