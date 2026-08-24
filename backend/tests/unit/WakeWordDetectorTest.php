<?php

use PHPUnit\Framework\TestCase;
use Atom\Voice\WakeWordDetector;

/**
 * Phase 34 — WakeWordDetector unit tests (5 tests).
 */
class WakeWordDetectorTest extends TestCase
{
    private WakeWordDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new WakeWordDetector(['hey atom', 'atom', 'jarvis'], 0.7);
    }

    public function testExactWakeWordDetection(): void
    {
        $res = $this->detector->detect('Hey Atom, what time is it?');

        $this->assertTrue($res['detected']);
        $this->assertSame('hey atom', $res['phrase']);
        $this->assertEqualsWithDelta(1.0, $res['confidence'], 0.001);
    }

    public function testSingleWordWakeTrigger(): void
    {
        $res = $this->detector->detect('Atom status report please');

        $this->assertTrue($res['detected']);
        $this->assertSame('atom', $res['phrase']);
    }

    public function testNonWakeWordPhraseIgnored(): void
    {
        $res = $this->detector->detect('The weather is nice today in San Francisco.');

        $this->assertFalse($res['detected']);
        $this->assertNull($res['phrase']);
    }

    public function testPhoneticSimilarityWakeWordDetection(): void
    {
        // "hey atum" is close to "hey atom"
        $res = $this->detector->detect('hey atum start pipeline');

        $this->assertTrue($res['detected']);
        $this->assertGreaterThanOrEqual(0.7, $res['confidence']);
    }

    public function testEmptyInputReturnsNotDetected(): void
    {
        $res = $this->detector->detect('   ');

        $this->assertFalse($res['detected']);
        $this->assertNull($res['phrase']);
        $this->assertSame(0.0, $res['confidence']);
    }
}
