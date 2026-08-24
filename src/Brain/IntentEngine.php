<?php

namespace Atom\Brain;

/**
 * IntentEngine — rich 14-category intent classifier.
 *
 * Supersedes the bare IntentDetector with structured entity extraction,
 * confidence scoring, and routing hints for the Brain's subsystem dispatcher.
 *
 * Categories
 * ----------
 * greeting          → local / conversation
 * memory_command    → local (remember/forget)
 * tool_request      → local (read_file, search_code, php_lint …)
 * agent_task        → agent orchestrator (Phase 17)
 * workflow_trigger  → workflow executor (Phase 18)
 * swarm_dispatch    → agent coordinator / swarm (Phase 19)
 * governance_query  → policy engine (Phase 22)
 * system_status     → local CLI / API status
 * coding            → LLM (code, debug, explain)
 * knowledge_lookup  → LLM + RAG
 * meta_question     → LLM (questions about Atom itself)
 * correction        → local correction handler
 * feedback          → local feedback handler
 * conversation      → LLM general chat
 */
class IntentEngine
{
    /** Ordered list of all valid intent identifiers. */
    public const INTENTS = [
        'greeting',
        'memory_command',
        'tool_request',
        'agent_task',
        'workflow_trigger',
        'swarm_dispatch',
        'governance_query',
        'system_status',
        'coding',
        'knowledge_lookup',
        'meta_question',
        'correction',
        'feedback',
        'conversation',
    ];

    /**
     * Classify the user's input and return a typed IntentResult.
     */
    public function classify(string $input): IntentResult
    {
        $lower = strtolower(trim($input));

        if (empty($lower)) {
            return new IntentResult('conversation', 30, [], 'llm');
        }

        // ── 1. Greeting ─────────────────────────────────────────────────────
        if ($this->matchesGreeting($lower)) {
            return new IntentResult('greeting', 95, [], 'local');
        }

        // ── 2. Correction ────────────────────────────────────────────────────
        if (preg_match('/^(no|correction|incorrect|wrong)\s*,?\s*/i', $lower)) {
            return new IntentResult('correction', 90, [], 'local');
        }

        // ── 3. Feedback ──────────────────────────────────────────────────────
        if (preg_match('/^(thumbs\s*(up|down)|good\s+answer|bad\s+answer|rate\s+this|that\s+was\s+(good|bad|great|wrong|helpful|terrible))/i', $lower)) {
            return new IntentResult('feedback', 90, [], 'local');
        }

        // ── 4. Memory commands ───────────────────────────────────────────────
        if (preg_match('/^(remember\s+(that|solution)|forget\s+memory)/i', $lower)) {
            $entities = [];
            if (preg_match('/^remember\s+that\s+(.+)$/i', $lower, $m)) {
                $entities['preference'] = trim($m[1]);
            }
            return new IntentResult('memory_command', 95, $entities, 'local');
        }

        // ── 5. Governance query ──────────────────────────────────────────────
        if (preg_match('/(governance|policy|kill.?switch|compliance|trust.?level|audit.?log|permission)/i', $lower)) {
            return new IntentResult('governance_query', 80, [], 'governance');
        }

        // ── 6. Swarm dispatch ────────────────────────────────────────────────
        if (preg_match('/(swarm|multi.?agent|spawn\s+agents?|coordinate\s+agents?|parallel\s+agents?)/i', $lower)) {
            $entities = $this->extractEntities($lower);
            return new IntentResult('swarm_dispatch', 85, $entities, 'swarm');
        }

        // ── 7. Workflow trigger ──────────────────────────────────────────────
        if (preg_match('/(trigger|run|execute|start)\s+(workflow|pipeline|automation|process)/i', $lower)
            || preg_match('/workflow\s+(trigger|run|start|execute)/i', $lower)
        ) {
            $entities = $this->extractEntities($lower);
            return new IntentResult('workflow_trigger', 85, $entities, 'workflow');
        }

        // ── 8. Agent task ────────────────────────────────────────────────────
        if (preg_match('/(agent\s+(task|run|execute)|run\s+agent|autonomous|orchestrate|plan\s+and\s+(execute|run))/i', $lower)) {
            $entities = $this->extractEntities($lower);
            return new IntentResult('agent_task', 85, $entities, 'agent');
        }

        // ── 9. Tool request ──────────────────────────────────────────────────
        if (preg_match('/(read\s+file|search\s+code|lint|create\s+file|patch\s+file|search\s+(in\s+)?codebase)/i', $lower)) {
            $entities = [];
            if (preg_match('/(?:read\s+file|open\s+file|show\s+file)\s+([^\s]+\.\w+)/i', $lower, $m)) {
                $entities['file'] = trim($m[1]);
            }
            return new IntentResult('tool_request', 90, $entities, 'local');
        }

        // ── 10. System status ────────────────────────────────────────────────
        if (preg_match('/(\/status|\/health|system\s+status|brain\s+status|atom\s+status|are\s+you\s+running|are\s+you\s+online)/i', $lower)) {
            return new IntentResult('system_status', 90, [], 'local');
        }

        // ── 11. Meta-question (about Atom itself) ────────────────────────────
        if (preg_match('/(what\s+(are|is)\s+(you|your|atom)|who\s+are\s+you|tell\s+me\s+about\s+(yourself|atom)|what\s+can\s+you\s+do|your\s+capabilities)/i', $lower)) {
            return new IntentResult('meta_question', 85, [], 'llm');
        }

        // ── 12. Knowledge lookup ─────────────────────────────────────────────
        if (preg_match('/(what\s+is|explain|describe|define|how\s+does|what\s+does|difference\s+between|compare)/i', $lower)
            && !preg_match('/(\\.php|\\.js|\\.css|\\.html|function|class|debug|error|fix|sql|query)/i', $lower)
        ) {
            return new IntentResult('knowledge_lookup', 70, [], 'llm');
        }

        // ── 13. Coding / debugging ───────────────────────────────────────────
        if (preg_match('/(\\.php|\\.js|\\.css|\\.html|\\.json|\\.dart|\\.cs|code|debug|explain\s+(this|my|the)\s+(code|error|bug|function)|fix|error|syntax|variable|function|class|database|table|sql|query|migration|controller|model|view)/i', $lower)) {
            $entities = $this->extractEntities($lower);
            return new IntentResult('coding', 80, $entities, 'llm');
        }

        // ── 14. Fallback: general conversation ──────────────────────────────
        return new IntentResult('conversation', 50, [], 'llm');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function matchesGreeting(string $lower): bool
    {
        $exact = ['hi', 'hello', 'hey', 'yo', 'howdy', 'good morning', 'good afternoon', 'good evening', 'good night'];
        if (in_array($lower, $exact, true)) {
            return true;
        }
        return (bool) preg_match('/^(hi|hello|hey|yo|howdy|good\s+(morning|afternoon|evening|night))(\s.*)?$/i', $lower);
    }

    /**
     * Extract common file paths, workflow names, and task descriptions from input.
     */
    private function extractEntities(string $lower): array
    {
        $entities = [];

        // File references
        if (preg_match('/([a-zA-Z0-9_\-\/]+\.(php|js|css|html|json|dart|cs|py|sql|md))/i', $lower, $m)) {
            $entities['file'] = $m[1];
        }

        // Workflow/pipeline name
        if (preg_match('/(?:workflow|pipeline|process)\s+["\']?([a-zA-Z0-9_\-\s]{2,40})["\']?/i', $lower, $m)) {
            $entities['workflow_name'] = trim($m[1]);
        }

        // Task description (first 80 chars after agent/task keywords)
        if (preg_match('/(?:task|run|execute|do)\s*:\s*(.{5,80})/i', $lower, $m)) {
            $entities['task'] = trim($m[1]);
        }

        return $entities;
    }
}
