<?php

namespace Atom\Swarm;

class SwarmStateMachine
{
    private static array $allowedTransitions = [
        'queued'           => ['planning', 'running', 'cancelled', 'failed'],
        'planning'         => ['running', 'cancelled', 'failed'],
        'running'          => ['waiting', 'verifying', 'synthesizing', 'waiting_approval', 'paused', 'completed', 'failed', 'cancelled', 'timeout'],
        'waiting'          => ['running', 'cancelled', 'failed'],
        'verifying'        => ['synthesizing', 'running', 'failed'],
        'synthesizing'     => ['completed', 'failed'],
        'waiting_approval' => ['running', 'cancelled', 'failed'],
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
            throw new \InvalidArgumentException("Invalid swarm state transition from '{$fromState}' to '{$toState}'.");
        }
    }
}
