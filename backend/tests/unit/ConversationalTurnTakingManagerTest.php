<?php

use PHPUnit\Framework\TestCase;
use Atom\Voice\ConversationalTurnTakingManager;

/**
 * Phase 34 — ConversationalTurnTakingManager unit tests (5 tests).
 */
class ConversationalTurnTakingManagerTest extends TestCase
{
    private ConversationalTurnTakingManager $turn;

    protected function setUp(): void
    {
        $this->turn = new ConversationalTurnTakingManager(800);
    }

    public function testInitialStateIsIdle(): void
    {
        $this->assertSame('IDLE', $this->turn->getState());
        $this->assertSame(0, $this->turn->getTurnCount());
    }

    public function testUserSpeechTransitionsToListening(): void
    {
        $this->turn->onUserSpeechDetected();
        $this->assertSame('LISTENING', $this->turn->getState());
    }

    public function testSilenceExceedingTimeoutTransitionsToThinking(): void
    {
        $this->turn->onUserSpeechDetected(); // LISTENING
        $this->turn->onSilenceDetected(900); // Exceeds 800ms limit

        $this->assertSame('THINKING', $this->turn->getState());
        $this->assertSame(1, $this->turn->getTurnCount());
    }

    public function testBargeInInterruptionWhenAssistantSpeaking(): void
    {
        $this->turn->onAssistantSpeakingStarted(); // SPEAKING
        $this->assertSame('SPEAKING', $this->turn->getState());

        // User speaks over assistant (Barge-in)
        $this->turn->onUserSpeechDetected();

        $this->assertSame('LISTENING', $this->turn->getState());
        $events = $this->turn->getEventHistory();
        $this->assertSame('INTERRUPTED', $events[0]['from']);
        $this->assertSame('LISTENING', $events[0]['to']);
    }

    public function testManualInterruptSignal(): void
    {
        $this->turn->onAssistantSpeakingStarted(); // SPEAKING
        $res = $this->turn->interrupt();

        $this->assertTrue($res['interrupted']);
        $this->assertSame('IDLE', $this->turn->getState());
    }
}
