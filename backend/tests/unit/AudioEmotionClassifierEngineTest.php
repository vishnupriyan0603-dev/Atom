<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Voice\AudioEmotionClassifierEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 68 — AudioEmotionClassifierEngine unit tests (6 tests).
 */
class AudioEmotionClassifierEngineTest extends TestCase
{
    private AudioEmotionClassifierEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AudioEmotionClassifierEngine(new SecretRedactor());
    }

    public function testClassifyHeroicBattleMood(): void
    {
        $res = $this->engine->classifyAcoustic(180.0, 30.0, 0.75);

        $this->assertTrue($res['success']);
        $this->assertSame('HEROIC_BATTLE', $res['primary_mood']);
        $this->assertSame('+15%', $res['ssml_modifiers']['pitch']);
        $this->assertSame('+10%', $res['ssml_modifiers']['rate']);
    }

    public function testClassifyEmpatheticCalmMood(): void
    {
        $res = $this->engine->classifyAcoustic(85.0, 5.0, 0.20);

        $this->assertTrue($res['success']);
        $this->assertSame('EMPATHETIC_CALM', $res['primary_mood']);
        $this->assertSame('-8%', $res['ssml_modifiers']['pitch']);
    }

    public function testClassifyAlertWarningMood(): void
    {
        $res = $this->engine->classifyAcoustic(240.0, 50.0, 0.90);

        $this->assertTrue($res['success']);
        $this->assertSame('ALERT_WARNING', $res['primary_mood']);
    }

    public function testClassifyTextIntentHeroic(): void
    {
        $res = $this->engine->classifyTextIntent("Omnitrix battle mode transform into Heatblast hero!");

        $this->assertTrue($res['success']);
        $this->assertSame('HEROIC_BATTLE', $res['primary_mood']);
    }

    public function testEmptyTextFailsGracefully(): void
    {
        $res = $this->engine->classifyTextIntent("");
        $this->assertFalse($res['success']);
    }

    public function testGetSupportedMoodsReturnsAllTiers(): void
    {
        $moods = $this->engine->getSupportedMoods();

        $this->assertArrayHasKey('HEROIC_BATTLE', $moods);
        $this->assertArrayHasKey('ANALYTICAL_COMMAND', $moods);
        $this->assertArrayHasKey('EMPATHETIC_CALM', $moods);
        $this->assertArrayHasKey('ALERT_WARNING', $moods);
    }
}
