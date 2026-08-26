<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomVoiceProsodyEngine;
use Atom\Security\SecretRedactor;

/**
 * Unit test suite for AtomVoiceProsodyEngine (Atom Brain Phase 4).
 */
class AtomVoiceProsodyEngineTest extends TestCase
{
    private AtomVoiceProsodyEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomVoiceProsodyEngine(new SecretRedactor());
    }

    public function testSynthesizeHeroicBen10Profile(): void
    {
        $res = $this->engine->synthesize('Welcome to Atom! Let us build something extraordinary today.', 'heroic_ben10', 'excited');
        $this->assertTrue($res['success']);
        $this->assertEquals('heroic_ben10', $res['profile']['key']);
        $this->assertGreaterThan(1.15, $res['prosody']['pitch']);
        $this->assertGreaterThan(1.15, $res['prosody']['rate']);
        $this->assertStringContainsString('<speak', $res['ssml']);
        $this->assertStringContainsString('</speak>', $res['ssml']);
        $this->assertEquals('en-IN', $res['web_speech_params']['lang']);
    }

    public function testTamilPhoneticDetectionAndProsody(): void
    {
        $tamilText = 'வணக்கம்! நான் உங்கள் ATOM AI அசிஸ்டன்ட். இன்று என்ன புராஜெக்ட் செய்கிறோம்?';
        $res = $this->engine->synthesize($tamilText, 'heroic_ben10', 'happy');

        $this->assertTrue($res['success']);
        $this->assertTrue($res['is_tamil']);
        $this->assertEquals('ta-IN', $res['profile']['lang']);
        $this->assertEquals('ta-IN', $res['web_speech_params']['lang']);
        $this->assertStringContainsString('xml:lang="ta-IN"', $res['ssml']);
    }

    public function testEmotionModulationCalmAndEmpathic(): void
    {
        $calmRes = $this->engine->synthesize('The database migration has succeeded with zero downtime.', 'calm_mentor', 'neutral');
        $this->assertTrue($calmRes['success']);
        $this->assertLessThanOrEqual(1.0, $calmRes['prosody']['pitch']);

        $empathicRes = $this->engine->synthesize('I understand you are frustrated by this bug. Let us fix it together.', 'empathic_companion', 'frustrated');
        $this->assertTrue($empathicRes['success']);
        $this->assertLessThan(1.0, $empathicRes['prosody']['rate']);
    }

    public function testSpokenTextPreparationMarkdownStripping(): void
    {
        $markdown = "Hello **Vishnu**! Check `config.php`:\n```php\n\$x = 10;\n```\nVisit [Docs](https://atom.dev).";
        $spoken = $this->engine->prepareSpokenText($markdown);

        $this->assertStringNotContainsString('```', $spoken);
        $this->assertStringNotContainsString('`', $spoken);
        $this->assertStringNotContainsString('**', $spoken);
        $this->assertStringContainsString('Code block omitted for audio.', $spoken);
        $this->assertStringContainsString('Hello Vishnu!', $spoken);
    }

    public function testFullDuplexStreamEventCoordination(): void
    {
        $startRes = $this->engine->handleStreamTurn('v_123', 'start_speech');
        $this->assertTrue($startRes['success']);
        $this->assertEquals('CONTINUE_STREAM', $startRes['action']);

        // User barge-in / interruption
        $interruptRes = $this->engine->handleStreamTurn('v_123', 'user_interruption');
        $this->assertTrue($interruptRes['success']);
        $this->assertEquals('HALT_AUDIO_PLAYBACK_AND_FLUSH', $interruptRes['action']);
        $this->assertEquals(120, $interruptRes['interruption_backoff_ms']);
    }

    public function testVoiceProfilesList(): void
    {
        $profiles = $this->engine->getVoiceProfiles();
        $this->assertIsArray($profiles);
        $this->assertArrayHasKey('heroic_ben10', $profiles);
        $this->assertArrayHasKey('calm_mentor', $profiles);
        $this->assertArrayHasKey('empathic_companion', $profiles);
        $this->assertArrayHasKey('fast_briefing', $profiles);
    }
}
