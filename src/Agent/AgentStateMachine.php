<?php

namespace Atom\Agent;

class AgentStateMachine
{
    private static array $allowedTransitions = [
        'pending'          => ['planning', 'cancelled', 'failed'],
        'planning'         => ['planned', 'failed', 'cancelled'],
        'planned'          => ['running', 'cancelled', 'failed'],
        'running'          => ['waiting_approval', 'verifying', 'replanning', 'completed', 'failed', 'timeout', 'cancelled', 'paused'],
        'waiting_approval' => ['running', 'cancelled', 'failed'],
        'verifying'        => ['completed', 'replanning', 'failed'],
        'replanning'       => ['running', 'failed', 'timeout', 'cancelled'],
        'paused'           => ['running', 'cancelled'],
        'completed'        => [],
        'failed'           => [],
        'cancelled'        => [],
        'timeout'          => [],
    ];

    public static function canTransition(string $fromState, string $toState): bool
    {
        $fromState = strtolower($fromState);
        $toState   = strtolower($toState);

        if ($fromState === $toState) {
            return true;
        }

        return isset(self::$allowedTransitions[$fromState]) &&
               in_array($toState, self::$allowedTransitions[$fromState], true);
    }

    public static function validateTransition(string $fromState, string $toState): void
    {
        if (!self::canTransition($fromState, $toState)) {
            throw new \InvalidArgumentException("Invalid agent state transition from '{$fromState}' to '{$toState}'.");
        }
    }
}
