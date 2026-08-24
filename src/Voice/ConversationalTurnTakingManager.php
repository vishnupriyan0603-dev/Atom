<?php

namespace Atom\Voice;

/**
 * Conversational Turn-Taking Manager — Phase 34
 *
 * State machine managing full-duplex conversational turns, Voice Activity Detection
 * (VAD) silence timeouts, and barge-in interruption handling.
 */
class ConversationalTurnTakingManager
{
    public const STATE_IDLE        = 'IDLE';
    public const STATE_LISTENING   = 'LISTENING';
    public const STATE_THINKING    = 'THINKING';
    public const STATE_SPEAKING    = 'SPEAKING';
    public const STATE_INTERRUPTED = 'INTERRUPTED';

    public const DEFAULT_SILENCE_TIMEOUT_MS = 800;

    private string $state = self::STATE_IDLE;
    private int $turnCount = 0;
    private int $silenceTimeoutMs;
    private array $eventHistory = [];
    private ?float $lastActivityTimestamp = null;

    public function __construct(int $silenceTimeoutMs = self::DEFAULT_SILENCE_TIMEOUT_MS)
    {
        $this->silenceTimeoutMs = $silenceTimeoutMs;
        $this->lastActivityTimestamp = microtime(true);
    }

    /**
     * Current state of the turn-taking manager.
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * User starts or continues speaking (VAD active).
     */
    public function onUserSpeechDetected(): string
    {
        $this->lastActivityTimestamp = microtime(true);

        if ($this->state === self::STATE_SPEAKING) {
            // Barge-in interruption!
            $this->transitionTo(self::STATE_INTERRUPTED, 'User barge-in interrupted assistant speech');
            $this->transitionTo(self::STATE_LISTENING, 'Switched to listening user input');
        } elseif ($this->state === self::STATE_IDLE || $this->state === self::STATE_THINKING) {
            $this->transitionTo(self::STATE_LISTENING, 'User speech started');
        }

        return $this->state;
    }

    /**
     * Silence detected in audio stream. If silence exceeds timeout, transition to THINKING.
     */
    public function onSilenceDetected(int $silenceDurationMs): string
    {
        if ($this->state === self::STATE_LISTENING && $silenceDurationMs >= $this->silenceTimeoutMs) {
            $this->transitionTo(self::STATE_THINKING, "Silence timeout ({$silenceDurationMs}ms) reached");
            $this->turnCount++;
        }
        return $this->state;
    }

    /**
     * Assistant starts outputting synthesized audio.
     */
    public function onAssistantSpeakingStarted(): string
    {
        if ($this->state === self::STATE_THINKING || $this->state === self::STATE_IDLE) {
            $this->transitionTo(self::STATE_SPEAKING, 'Assistant audio playback started');
        }
        return $this->state;
    }

    /**
     * Assistant finishes outputting audio.
     */
    public function onAssistantSpeakingCompleted(): string
    {
        if ($this->state === self::STATE_SPEAKING) {
            $this->transitionTo(self::STATE_IDLE, 'Assistant audio playback completed');
        }
        return $this->state;
    }

    /**
     * Explicitly trigger interruption signal (e.g. from UI button or hotkey).
     */
    public function interrupt(): array
    {
        $prev = $this->state;
        if ($this->state === self::STATE_SPEAKING || $this->state === self::STATE_THINKING) {
            $this->transitionTo(self::STATE_INTERRUPTED, 'Manual interruption signal');
            $this->transitionTo(self::STATE_IDLE, 'Ready for next turn');
            return ['interrupted' => true, 'previous_state' => $prev, 'new_state' => $this->state];
        }
        return ['interrupted' => false, 'state' => $this->state];
    }

    /**
     * Gets turn count.
     */
    public function getTurnCount(): int
    {
        return $this->turnCount;
    }

    /**
     * Gets recent state transition events.
     */
    public function getEventHistory(int $limit = 20): array
    {
        return array_slice(array_reverse($this->eventHistory), 0, $limit);
    }

    /**
     * Resets state machine.
     */
    public function reset(): void
    {
        $this->state = self::STATE_IDLE;
        $this->turnCount = 0;
        $this->eventHistory = [];
        $this->lastActivityTimestamp = microtime(true);
    }

    private function transitionTo(string $newState, string $reason): void
    {
        $oldState = $this->state;
        $this->state = $newState;
        $this->eventHistory[] = [
            'from'      => $oldState,
            'to'        => $newState,
            'reason'    => $reason,
            'timestamp' => microtime(true),
        ];
    }
}
