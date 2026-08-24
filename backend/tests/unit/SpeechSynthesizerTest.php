<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\Voice\SpeechSynthesizer;

/**
 * Phase 24 — SpeechSynthesizer unit tests (5 tests).
 */
class SpeechSynthesizerTest extends TestCase
{
    private SpeechSynthesizer $synthesizer;

    protected function setUp(): void
    {
        $this->synthesizer = new SpeechSynthesizer();
    }

    public function testSynthesizeReturnsValidSsmlAndInstructions(): void
    {
        $result = $this->synthesizer->synthesize('Hello Atom world');
        $this->assertTrue($result['success']);
        $this->assertSame('Hello Atom world', $result['text']);
        $this->assertStringContainsString('<speak><p>Hello Atom world</p></speak>', $result['ssml']);
        $this->assertArrayHasKey('speech_instructions', $result);
    }

    public function testSynthesizeRejectsEmptyText(): void
    {
        $result = $this->synthesizer->synthesize('   ');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('cannot be empty', $result['error']);
    }

    public function testAvailableVoicesList(): void
    {
        $voices = $this->synthesizer->getVoices();
        $this->assertArrayHasKey('en-US-Neural2-F', $voices);
        $this->assertArrayHasKey('en-IN-Standard-A', $voices);
        $this->assertSame('female', $voices['en-IN-Standard-A']['gender']);
    }

    public function testSyntheticWavHeaderGeneration(): void
    {
        $result = $this->synthesizer->synthesize('Test audio synthesis', 'en-US-Neural2-D', 'local_wav');
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['audio_base64']);
        $decoded = base64_decode($result['audio_base64']);
        $this->assertStringStartsWith('RIFF', $decoded);
        $this->assertStringContainsString('WAVE', substr($decoded, 0, 12));
    }

    public function testDurationEstimation(): void
    {
        $result = $this->synthesizer->synthesize('This is a longer test sentence with several words for duration estimation.');
        $this->assertGreaterThanOrEqual(1, $result['estimated_duration_sec']);
    }
}
