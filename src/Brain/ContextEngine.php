<?php

namespace Atom\Brain;

/**
 * ContextEngine — cross-turn conversational context tracker.
 *
 * Tracks WHAT is being discussed in the current conversation thread:
 *  - Active topic
 *  - Recently referenced files / entities
 *  - Inferred conversation goal
 *  - Turn counter
 *
 * State is kept in-memory for the current process lifetime and optionally
 * persisted to the MemoryManager's working-memory layer for session continuity.
 *
 * This class does NOT store user secrets, PII beyond referenced file names,
 * or any information that bypasses the Governance PolicyEngine.
 */
class ContextEngine
{
    /** Maximum number of turns to retain in the window. */
    private const MAX_TURNS       = 20;
    /** Maximum entity references stored per session. */
    private const MAX_ENTITIES    = 30;

    /** Active topic detected from conversation. */
    private string $activeTopic = '';

    /** Referenced files/classes/functions/tables. */
    private array $referencedEntities = [];

    /** Inferred high-level goal of the current thread. */
    private string $inferredGoal = '';

    /** Full turn history: [{role, snippet, intent}] */
    private array $turnHistory = [];

    /** Callback to persist context summary to MemoryManager session storage. */
    private ?\Closure $persistCallback = null;

    public function __construct(?\Closure $persistCallback = null)
    {
        $this->persistCallback = $persistCallback;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Update context after each conversation turn.
     *
     * @param string $input    The user's input for this turn.
     * @param string $response The assistant's response for this turn.
     * @param string $intent   The intent classified for this turn (from IntentEngine).
     */
    public function update(string $input, string $response, string $intent = 'conversation'): void
    {
        // Update topic based on intent
        $this->updateTopic($input, $intent);

        // Extract and store entity references
        $this->extractAndStoreEntities($input);

        // Infer/refine the conversation goal
        $this->inferGoal($input, $intent);

        // Store turn
        $this->turnHistory[] = [
            'role'    => 'user',
            'snippet' => mb_substr($input, 0, 120),
            'intent'  => $intent,
        ];
        $this->turnHistory[] = [
            'role'    => 'assistant',
            'snippet' => mb_substr($response, 0, 120),
            'intent'  => $intent,
        ];

        // Bound the window
        if (count($this->turnHistory) > self::MAX_TURNS * 2) {
            $this->turnHistory = array_slice($this->turnHistory, -(self::MAX_TURNS * 2));
        }

        // Persist to session storage if callback is registered
        if ($this->persistCallback !== null) {
            ($this->persistCallback)($this->getSummary());
        }
    }

    /**
     * Get the full context summary as an array.
     * This is injected into the system prompt via ContextBuilder.
     */
    public function getSummary(): array
    {
        return [
            'active_topic'         => $this->activeTopic,
            'inferred_goal'        => $this->inferredGoal,
            'referenced_entities'  => array_values($this->referencedEntities),
            'turn_count'           => (int) (count($this->turnHistory) / 2),
        ];
    }

    /**
     * Build the context block string for injection into the system prompt.
     */
    public function buildContextBlock(): string
    {
        $summary = $this->getSummary();
        if (empty($summary['active_topic']) && empty($summary['referenced_entities'])) {
            return '';
        }

        $lines = ['--- ACTIVE CONVERSATION CONTEXT ---'];

        if (!empty($summary['active_topic'])) {
            $lines[] = "Current Topic: " . $summary['active_topic'];
        }
        if (!empty($summary['inferred_goal'])) {
            $lines[] = "Inferred Goal: " . $summary['inferred_goal'];
        }
        if (!empty($summary['referenced_entities'])) {
            $lines[] = "Referenced Items: " . implode(', ', array_slice($summary['referenced_entities'], -8));
        }
        $lines[] = "Conversation Turns: " . $summary['turn_count'];
        $lines[] = '------------------------------------';

        return implode("\n", $lines);
    }

    /**
     * Reset all context state (start a fresh conversation thread).
     */
    public function reset(): void
    {
        $this->activeTopic         = '';
        $this->referencedEntities  = [];
        $this->inferredGoal        = '';
        $this->turnHistory         = [];
    }

    public function getActiveTopic(): string
    {
        return $this->activeTopic;
    }

    public function getInferredGoal(): string
    {
        return $this->inferredGoal;
    }

    public function getReferencedEntities(): array
    {
        return array_values($this->referencedEntities);
    }

    public function getTurnCount(): int
    {
        return (int) (count($this->turnHistory) / 2);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal
    // ──────────────────────────────────────────────────────────────────────────

    private function updateTopic(string $input, string $intent): void
    {
        $topicMap = [
            'coding'           => 'Software Development',
            'agent_task'       => 'Agent Orchestration',
            'workflow_trigger' => 'Workflow Automation',
            'swarm_dispatch'   => 'Multi-Agent Swarm',
            'governance_query' => 'Governance & Policy',
            'knowledge_lookup' => 'Knowledge Research',
            'memory_command'   => 'Memory Management',
            'system_status'    => 'System Status',
            'meta_question'    => 'About Atom',
        ];

        if (isset($topicMap[$intent])) {
            $this->activeTopic = $topicMap[$intent];
        } elseif (empty($this->activeTopic)) {
            $this->activeTopic = 'General Conversation';
        }
    }

    private function extractAndStoreEntities(string $input): void
    {
        // Extract file references
        preg_match_all('/([a-zA-Z0-9_\-\/]+\.(php|js|css|html|json|dart|cs|py|sql|md))/i', $input, $fileMatches);
        foreach ($fileMatches[1] as $file) {
            $key = 'file:' . basename($file);
            $this->referencedEntities[$key] = basename($file);
        }

        // Extract class / function names
        preg_match_all('/(?:class|function|method)\s+([A-Za-z_][A-Za-z0-9_]+)/i', $input, $classMatches);
        foreach ($classMatches[1] as $name) {
            $this->referencedEntities['symbol:' . $name] = $name . '()';
        }

        // Extract DB table names
        preg_match_all('/(?:table|from|into|update)\s+`?([a-z_][a-z0-9_]{2,})`?/i', $input, $tableMatches);
        foreach ($tableMatches[1] as $table) {
            $this->referencedEntities['table:' . $table] = 'table:' . $table;
        }

        // Bound the entity store
        if (count($this->referencedEntities) > self::MAX_ENTITIES) {
            $this->referencedEntities = array_slice($this->referencedEntities, -self::MAX_ENTITIES, null, true);
        }
    }

    private function inferGoal(string $input, string $intent): void
    {
        $goalHints = [
            'coding'           => 'Build or debug software code',
            'agent_task'       => 'Execute an autonomous agent task',
            'workflow_trigger' => 'Automate a development workflow',
            'swarm_dispatch'   => 'Coordinate a multi-agent swarm',
            'governance_query' => 'Review governance and compliance policies',
            'knowledge_lookup' => 'Research and understand a technical topic',
        ];

        if (isset($goalHints[$intent])) {
            $this->inferredGoal = $goalHints[$intent];
        }

        // Detect specific goal from input phrasing
        if (preg_match('/(?:i\s+(?:want|need|am\s+trying)\s+to|how\s+(?:do|can)\s+i)\s+(.{5,60})/i', $input, $m)) {
            $this->inferredGoal = ucfirst(trim($m[1]));
        }
    }
}
