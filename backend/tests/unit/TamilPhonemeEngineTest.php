<?php

namespace Tests\Unit;

use Atom\Voice\TamilPhonemeEngine;
use PHPUnit\Framework\TestCase;

/**
 * Phase 41 / Voice — TamilPhonemeEngine unit tests (5 tests).
 */
class TamilPhonemeEngineTest extends TestCase
{
    private TamilPhonemeEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new TamilPhonemeEngine();
    }

    public function testAnalyzePhoneticsDetectsRetroflexesAndGemination(): void
    {
        $text = "தமிழ்நாடு வாழ்க! வெற்றி நமதே.";
        $result = $this->engine->analyzePhonetics($text);

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['retroflex_count']);
        $this->assertGreaterThan(0, $result['gemination_count']);
        $this->assertCount(4, $result['words']);
    }

    public function testAnalyzePhoneticsHandlesEmptyString(): void
    {
        $result = $this->engine->analyzePhonetics('   ');
        $this->assertEmpty($result);
    }

    public function testFormatProsodicBreaksInsertsSsmlPauses(): void
    {
        $text = "ஆம்னிட்ரிக்ஸ் ரெடி! இட்ஸ் ஹீரோ டைம்.";
        $formatted = $this->engine->formatProsodicBreaks($text);

        $this->assertStringContainsString('<break time="150ms"/>', $formatted);
    }

    public function testHeroicRhythmClassification(): void
    {
        $text = "ஏலியன் வரட்டும், நான் பாத்துக்கிறேன்! ஆம்னிட்ரிக்ஸ் ரெடியா இருக்கு... இட்ஸ் ஹீரோ டைம்!";
        $result = $this->engine->analyzePhonetics($text);

        $this->assertEquals('punchy_heroic', $result['pronunciation_rhythm']);
    }

    public function testWordStressClassification(): void
    {
        $text = "வெற்றி";
        $result = $this->engine->analyzePhonetics($text);

        $this->assertEquals('elevated', $result['words'][0]['stress_level']);
    }
}
