<?php

namespace Atom\Brain;

class ContextBuilder
{
    private string $workspaceRoot;

    /** Loaded once from config/rules/system.md */
    private string $masterPrompt = '';

    /** Fallback inline identity (used only if system.md is missing) */
    private array $fallbackInstructions = [
        "You are ATOM, a personal AI development assistant and coding agent.",
        "Your expertise covers Core PHP, MySQL, CSS/HTML, Javascript, Bootstrap, CRM, ERP, and billing systems.",
        "Keep answers clear, technically accurate, and formatted in clean markdown.",
        "Prioritize safety: recommend validation, output escaping, and prepared statements.",
        "If you do not know the answer or lack the required context, say so clearly instead of guessing.",
        "Answer strictly from the provided context. Do not fabricate sources, file names, or citations.",
        "When answering from knowledge chunks, cite the Source Document and Page Number.",
        "Prefer concise, actionable answers over long explanations unless detail is requested.",
    ];

    /** Agentic tool schema — always appended after the master rules */
    private array $toolSchema = [
        "\n--- AGENTIC TOOL ACCESS & RESPONSE SCHEMA ---",
        "You can execute local development tools to inspect or edit the project workspace.",
        "REPLY IN PLAIN TEXT: Always respond to the user in clean, natural markdown text.",
        "NEVER wrap a normal reply in JSON or code fences unless you are actually calling a tool.",
        "",
        "TOOL CALLS ONLY: Only when you need to execute a tool, output a single JSON object (no surrounding text), containing your explanation and a 'tool_call' request:",
        "{",
        "  \"explanation\": \"Short text telling the user what you are doing.\",",
        "  \"tool_call\": {",
        "    \"name\": \"tool_name\",",
        "    \"arguments\": {",
        "       \"param_key\": \"param_val\"",
        "    }",
        "  }",
        "}",
        "If you are ready to respond to the user without a tool, reply with plain markdown text only.",
        "\nALLOWED TOOLS SCHEMA:",
        "- 'read_file': Reads file text. Args: {\"file_path\": \"filename\"}",
        "- 'search_code': Scans codebase. Args: {\"query\": \"search_term\"}",
        "- 'php_lint': Validates PHP syntax. Args: {\"file_path\": \"filename\"}",
        "- 'create_file': Creates a new file. Args: {\"file_path\": \"filename\", \"content\": \"text\"}",
        "- 'patch_file': Replaces code block. Args: {\"file_path\": \"filename\", \"target_content\": \"old\", \"replacement_content\": \"new\", \"interactive\": true}",
        "Do NOT call tools that are not listed here.",
    ];

    public function __construct(string $workspaceRoot)
    {
        $this->workspaceRoot = $workspaceRoot;
        $this->loadMasterPrompt();
    }

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    /**
     * Builds the full system message for each request.
     *
     * Structure:
     *  1. Master ATOM rules  (from config/rules/system.md)
     *  2. Agentic tool schema
     *  3. Environment block  (workspace root, PHP version)
     *  4. Personalization    (owner profile, session memory, preferences)
     *  5. Workspace stats    (file counts, extensions)
     *  6. Long-term memories
     *  7. RAG knowledge chunks
     */
    public function buildSystemMessage(array $workspaceStats = []): array
    {
        $parts = [];

        // 1. Master rules
        if (!empty($this->masterPrompt)) {
            $parts[] = $this->masterPrompt;
        } else {
            $parts[] = implode("\n", $this->fallbackInstructions);
        }

        // 2. Agentic tool schema
        $parts[] = implode("\n", $this->toolSchema);

        // 3. Environment
        $parts[] = "\n--- ENVIRONMENT ---";
        $parts[] = "Current Active Workspace: " . $this->workspaceRoot;
        $parts[] = "PHP Version: " . PHP_VERSION;

        // 3a. Phase 23 — Awareness block (time, device, workspace delta)
        if (!empty($workspaceStats['awareness_block'])) {
            $parts[] = "\n" . $workspaceStats['awareness_block'];
        }

        // 3b. Phase 23 — Active conversation context block
        if (!empty($workspaceStats['context_block'])) {
            $parts[] = $workspaceStats['context_block'];
        }

        // 3c. Phase 23 — Personality & communication style block
        if (!empty($workspaceStats['personality_block'])) {
            $parts[] = "\n" . $workspaceStats['personality_block'];
        }
        if (!empty($workspaceStats['personalization_prompt'])) {
            $parts[] = $workspaceStats['personalization_prompt'];
        }

        // 4b. Owner preferred name
        if (!empty($workspaceStats['owner_preferred_name'])) {
            $parts[] = "You are speaking with your owner, " . $workspaceStats['owner_preferred_name'] . ".";
        }

        // 4c. Detailed Personal Profile from JSON
        if (!empty($workspaceStats['personal_profile'])) {
            $profile = $workspaceStats['personal_profile'];
            $parts[] = "\n--- OWNER PERSONAL PROFILE ---";
            $parts[] = "Owner Name: " . ($profile['name'] ?? 'Vishnupriyan R') . " (Nickname: " . ($profile['nickname'] ?? 'Vichu') . ")";
            $parts[] = "Role: " . ($profile['role'] ?? '');
            $parts[] = "Location: " . ($profile['location'] ?? '');
            if (!empty($profile['communication_preferences'])) {
                $parts[] = "Explanation Style: " . ($profile['communication_preferences']['explanation_style'] ?? '');
                $parts[] = "Teaching Approach: " . ($profile['communication_preferences']['approach'] ?? '');
            }
            if (!empty($profile['learning_goals'])) {
                $parts[] = "Learning Goals: " . implode(', ', array_slice($profile['learning_goals'], 0, 8));
            }
            $parts[] = "-------------------------------\n";
        }

        // 4d. Session Memory
        if (!empty($workspaceStats['session_memory'])) {
            $session = $workspaceStats['session_memory'];
            $parts[] = "\n--- ACTIVE SESSION CONTEXT ---";
            if (!empty($session['opened_files'])) {
                $parts[] = "Recently Opened Files: " . implode(', ', $session['opened_files']);
            }
            if (!empty($session['current_task'])) {
                $parts[] = "Current Task: " . $session['current_task'];
            }
            $parts[] = "------------------------------\n";
        }

        // 5. Workspace stats
        if (!empty($workspaceStats)) {
            $parts[] = "Files scanned in workspace: " . ($workspaceStats['total_files'] ?? 0);
            if (!empty($workspaceStats['extensions'])) {
                $extList = [];
                foreach (array_slice($workspaceStats['extensions'], 0, 5) as $ext => $count) {
                    $extList[] = ".$ext ($count)";
                }
                $parts[] = "Workspace breakdown: " . implode(', ', $extList);
            }
        }

        // 6. Long-term memories (last 15)
        if (!empty($workspaceStats['memories'])) {
            $parts[] = "\n--- LONG TERM MEMORIES & USER PREFERENCES ---";
            foreach (array_slice($workspaceStats['memories'], -15) as $mem) {
                $parts[] = "- [" . $mem['type'] . "] " . $mem['memory_key'] . ": " . $mem['memory_value'];
            }
            $parts[] = "-------------------------------------------\n";
        }

        // 7. RAG knowledge chunks (max 3)
        if (!empty($workspaceStats['knowledge'])) {
            $parts[] = "\n--- RELEVANT KNOWLEDGE DATABASE REFERENCE CHUNKS ---";
            foreach (array_slice($workspaceStats['knowledge'], 0, 3) as $chunk) {
                $parts[] = "Source Document: " . $chunk['title'] . " (" . $chunk['filename'] . ")";
                $parts[] = "Page Number: " . $chunk['page_number'];
                $chunkText = substr($chunk['chunk_text'], 0, 900);
                if (strlen($chunk['chunk_text']) > 900) {
                    $chunkText .= " [TRUNCATED]";
                }
                $parts[] = "Excerpt content: " . $chunkText;
                $parts[] = "---";
            }
            $parts[] = "Ensure you cite the Source Document and Page Number in your reply when answering from this knowledge.\n";
        }

        return [
            'role'    => 'system',
            'content' => implode("\n", $parts),
        ];
    }

    /**
     * Combines system context, history, and user input with token optimization.
     */
    public function assemble(array $history, array $userInputMessage, array $workspaceStats = []): array
    {
        $messages   = [];
        $messages[] = $this->buildSystemMessage($workspaceStats);

        // Token Optimization: Bound history window to last 6 messages to reduce input token usage by ~60%
        $maxHistory = (int)(getenv('ATOM_MAX_HISTORY_MESSAGES') ?: 6);
        foreach (array_slice($history, -$maxHistory) as $msg) {
            $content = $msg['content'] ?? '';
            // Compress old assistant replies that contain large tool results or code blocks (>1200 chars)
            if (($msg['role'] ?? '') === 'assistant' && strlen($content) > 1200) {
                $content = substr($content, 0, 1200) . "\n... [PREVIOUS CONTEXT TRUNCATED TO SAVE TOKENS]";
            }
            $messages[] = ['role' => $msg['role'], 'content' => $content];
        }

        $messages[] = $userInputMessage;

        return $messages;
    }

    // ----------------------------------------------------------------
    // Internal
    // ----------------------------------------------------------------

    /**
     * Load the master system prompt from config/rules/system.md.
     * Searches relative to the workspace root and the src/ parent directory.
     */
    private function loadMasterPrompt(): void
    {
        $candidates = [
            rtrim($this->workspaceRoot, '/\\') . '/config/rules/system.md',
            dirname(__DIR__, 2) . '/config/rules/system.md',
        ];

        foreach ($candidates as $path) {
            $path = str_replace('\\', '/', $path);
            if (is_file($path)) {
                $content = file_get_contents($path);
                if ($content !== false && strlen(trim($content)) > 0) {
                    $this->masterPrompt = trim($content);
                    return;
                }
            }
        }

        $this->masterPrompt = '';
    }
}
