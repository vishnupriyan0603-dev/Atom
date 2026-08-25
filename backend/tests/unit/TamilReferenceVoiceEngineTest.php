<?php

namespace Tests\Unit;

use Atom\Voice\TamilReferenceVoiceEngine;
use PHPUnit\Framework\TestCase;

/**
 * Phase 41 / Voice — TamilReferenceVoiceEngine unit tests (6 tests).
 */
class TamilReferenceVoiceEngineTest extends TestCase
{
    private TamilReferenceVoiceEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new TamilReferenceVoiceEngine();
    }

    public function testExtractVoiceProfileReturnsValidAcousticParameters(): void
    {
        $profile = $this->engine->extractVoiceProfile();
        $this->assertIsArray($profile);
        $this->assertEquals('atom_ben10_tamil_heroic', $profile['voice_id']);
        $this->assertEquals('ta-IN', $profile['language']);
        $this->assertEquals(245.0, $profile['f0_fundamental_hz']);
        $this->assertEquals(1.18, $profile['pitch_shift_factor']);
        $this->assertEquals(1.18, $profile['speech_rate']);
        $this->assertArrayHasKey('eq_profile', $profile);
        $this->assertCount(10, $profile['eq_profile']);
    }

    public function testGenerateTamilSpeechInstructionsProducesSsml(): void
    {
        $tamilText = "வணக்கம் விச்சு! நான் ஆட்டம் AI அசிஸ்டன்ட்.";
        $instructions = $this->engine->generateTamilSpeechInstructions($tamilText);

        $this->assertIsArray($instructions);
        $this->assertTrue($instructions['success']);
        $this->assertTrue($instructions['is_tamil_script']);
        $this->assertStringContainsString('<speak>', $instructions['ssml']);
        $this->assertStringContainsString('pitch="+18%"', $instructions['ssml']);
        $this->assertStringContainsString('rate="1.18"', $instructions['ssml']);
        $this->assertEquals('ta-IN', $instructions['web_speech_params']['lang']);
    }

    public function testGenerateTamilSpeechInstructionsRejectsEmptyText(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->engine->generateTamilSpeechInstructions('   ');
    }

    public function testVoiceProfileCachingAvoidsRedundantDiskReads(): void
    {
        $profile1 = $this->engine->extractVoiceProfile();
        $profile2 = $this->engine->extractVoiceProfile();

        $this->assertSame($profile1, $profile2);
    }

    public function testResolveAudioPathFindsReferenceMp3(): void
    {
        $path = $this->engine->resolveAudioPath();
        $this->assertNotNull($path);
        $this->assertFileExists($path);
    }

    public function testDynamicPitchVariationWithinSafeBounds(): void
    {
        $profile = $this->engine->extractVoiceProfile();
        $this->assertGreaterThan(0.0, $profile['dynamic_pitch_var']);
        $this->assertLessThan(0.5, $profile['dynamic_pitch_var']);
        $this->assertEquals(48.5, $profile['snr_ratio_db']);
    }
}
