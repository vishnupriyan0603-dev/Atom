<?php

namespace Atom\Workflow;

class WorkflowStateMachine
{
    private static array $allowedTransitions = [
        'queued'           => ['running', 'cancelled', 'failed'],
        'running'          => ['waiting_approval', 'waiting_delay', 'paused', 'retrying', 'completed', 'failed', 'cancelled', 'timeout'],
        'waiting_approval' => ['running', 'cancelled', 'failed'],
        'waiting_delay'    => ['running', 'cancelled', 'failed'],
        'paused'           => ['running', 'cancelled'],
        'retrying'         => ['running', 'failed', 'cancelled'],
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
            throw new \InvalidArgumentException("Invalid workflow state transition from '{$fromState}' to '{$toState}'.");
        }
    }
}
