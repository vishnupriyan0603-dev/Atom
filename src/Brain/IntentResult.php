<?php

namespace Atom\Brain;

/**
 * IntentResult — typed value object produced by IntentEngine.
 *
 * Properties
 * ----------
 * intent      : string  — intent category (see IntentEngine::INTENTS)
 * confidence  : int     — 0-100 confidence score
 * entities    : array   — extracted key→value entities (e.g. ['file' => 'index.php'])
 * routingHint : string  — 'llm'|'local'|'agent'|'workflow'|'swarm'|'governance'
 */
class IntentResult
{
    public readonly string $intent;
    public readonly int    $confidence;
    public readonly array  $entities;
    public readonly string $routingHint;

    public function __construct(
        string $intent,
        int    $confidence  = 50,
        array  $entities    = [],
        string $routingHint = 'llm'
    ) {
        $this->intent      = $intent;
        $this->confidence  = max(0, min(100, $confidence));
        $this->entities    = $entities;
        $this->routingHint = $routingHint;
    }

    public function toArray(): array
    {
        return [
            'intent'       => $this->intent,
            'confidence'   => $this->confidence,
            'entities'     => $this->entities,
            'routing_hint' => $this->routingHint,
        ];
    }
}
