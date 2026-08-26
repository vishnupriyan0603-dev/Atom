<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomVoiceProsodyEngine;
use Atom\Security\SecretRedactor;

/**
 * Security and safety pass test suite for Atom Brain Phase 4 (Voice Duplex & Prosody Engine).
 */
class AtomBrainPhase4SecurityPassTest extends TestCase
{
    private AtomVoiceProsodyEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomVoiceProsodyEngine(new SecretRedactor());
    }

    public function testSsmlInjectionDefense(): void
    {
        $maliciousXml = 'Hello </prosody><script>alert("xss")</script><break time="1000s"/>';
        $res = $this->engine->synthesize($maliciousXml, 'heroic_ben10');

        $this->assertTrue($res['success']);
        // Verify XML tags are neutralized and cannot break out of the prosody wrapper
        $this->assertStringNotContainsString('<script>', $res['ssml']);
        $this->assertStringNotContainsString('</prosody><script>', $res['ssml']);
        $this->assertStringStartsWith('<speak', $res['ssml']);
        $this->assertStringEndsWith('</speak>', $res['ssml']);
    }

    public function testSecretRedactionInVoiceSynthesis(): void
    {
        $secretText = 'Your active database password is secret = "ProdPass999!" and token sk-proj-1234567890abcdef1234567890abcdef';
        $res = $this->engine->synthesize($secretText);

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('sk-proj-1234567890abcdef1234567890abcdef', $res['spoken_text']);
        $this->assertStringNotContainsString('ProdPass999!', $res['spoken_text']);
        $this->assertStringNotContainsString('sk-proj-1234567890abcdef1234567890abcdef', $res['ssml']);
    }

    public function testHighThroughputVoiceSynthesis(): void
    {
        $start = microtime(true);
        for ($i = 0; $i < 50; $i++) {
            $this->engine->synthesize("Audio phrase synthesis turn {$i} for performance testing.", 'heroic_ben10', 'excited');
        }
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.5, $elapsed, '50 voice syntheses should complete within 1.5s');
    }

    public function testInvalidAndEmptyTextHandling(): void
    {
        $resEmpty = $this->engine->synthesize('');
        $this->assertFalse($resEmpty['success']);

        $resUnsupportedEvent = $this->engine->handleStreamTurn('s_1', 'unauthorized_dangerous_event');
        $this->assertFalse($resUnsupportedEvent['success']);
    }
}
