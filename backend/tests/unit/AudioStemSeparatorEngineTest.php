<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioStemSeparatorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 73 — AudioStemSeparatorEngine unit tests (6 tests).
 */
class AudioStemSeparatorEngineTest extends TestCase
{
    private AudioStemSeparatorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AudioStemSeparatorEngine(new SecretRedactor());
    }

    public function testSeparateStemsProducesVocalAndInstrumentalStreams(): void
    {
        $frames = [0.1, 0.4, 0.7, 0.5, -0.2, -0.6, -0.4, 0.3];
        $res = $this->engine->separateStems($frames, 0.85);

        $this->assertTrue($res['success']);
        $this->assertCount(count($frames), $res['vocal_stem']);
        $this->assertCount(count($frames), $res['instrumental_stem']);
        $this->assertGreaterThan(50.0, $res['vocal_purity_pct']);
        $this->assertGreaterThan(0.0, $res['snr_db']);
    }

    public function testMixStemsCombinesBuffersWithGains(): void
    {
        $vocal = [0.5, 0.8, -0.4];
        $inst = [0.2, 0.1, -0.1];

        $mix = $this->engine->mixStems($vocal, $inst, 1.0, 0.5);

        $this->assertTrue($mix['success']);
        $this->assertSame(3, $mix['mixed_samples_count']);
        $this->assertSame(0.6, $mix['mixed_frames'][0]); // 0.5*1.0 + 0.2*0.5 = 0.6
    }

    public function testEmptyFramesArrayFailsGracefully(): void
    {
        $res = $this->engine->separateStems([]);
        $this->assertFalse($res['success']);
    }

    public function testAudioSampleClippingBetweenMinusOneAndPlusOne(): void
    {
        $frames = [2.5, -3.0, 0.5];
        $res = $this->engine->separateStems($frames);

        foreach ($res['vocal_stem'] as $v) {
            $this->assertLessThanOrEqual(1.0, $v);
            $this->assertGreaterThanOrEqual(-1.0, $v);
        }
    }

    public function testGetFrequencyBandsReturnsAllTiers(): void
    {
        $bands = $this->engine->getFrequencyBands();

        $this->assertArrayHasKey('bass', $bands);
        $this->assertArrayHasKey('vocals', $bands);
        $this->assertArrayHasKey('instruments', $bands);
    }

    public function testVocalIsolationStrengthClamping(): void
    {
        $frames = [0.2, 0.4];
        $res = $this->engine->separateStems($frames, 5.0); // clamped to 1.0

        $this->assertSame(1.0, $res['vocal_isolation_strength']);
    }
}
