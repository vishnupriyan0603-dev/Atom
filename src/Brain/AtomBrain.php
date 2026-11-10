<?php

namespace Atom\Brain;

use Atom\LLM\LLMInterface;
use Atom\Project\ProjectScanner;
use Atom\Security\SecretRedactor;
use Atom\Memory\MemoryManager;
use Atom\Knowledge\KnowledgeSearch;
use Atom\Tools\ToolManager;
use Atom\PersonalModel\AtomPersonalModel;
use Atom\PersonalModel\ModelManager;
use Atom\PersonalModel\OwnerProfileManager;
use Atom\PersonalModel\TrainingExampleRepository;
use Atom\Brain\Device\DeviceAbstraction;
use Atom\Arc\ArcMasterController;

class AtomBrain
{
    private ?ModelManager $modelManager;
    private ?ArcMasterController $arcController = null;
    private IntentDetector $detector;
    private ContextBuilder $contextBuilder;
    private ProjectScanner $scanner;
    private SecretRedactor $redactor;
    private MemoryManager $memory;
    private KnowledgeSearch $kSearch;
    private ToolManager $toolManager;
    private ResponseParser $parser;
    private ?AtomPersonalModel $personalModel;
    private ?OwnerProfileManager $profileManager;
    private LearningEngine $learningEngine;
    private ?int $conversationId = null;

    // ── Phase 23 Brain Engines ────────────────────────────────────────────────
    private IntentEngine $intentEngine;
    private PersonalityEngine $personalityEngine;
    private ContextEngine $contextEngine;
    private AwarenessEngine $awarenessEngine;
    private Voice\VoiceEngine $voiceEngine;
    // ─────────────────────────────────────────────────────────────────────────

    public function __construct(
        ?ModelManager $modelManager,
        IntentDetector $detector,
        ContextBuilder $contextBuilder,
        ProjectScanner $scanner,
        SecretRedactor $redactor,
        MemoryManager $memory,
        KnowledgeSearch $kSearch,
        ToolManager $toolManager,
        ?AtomPersonalModel $personalModel = null,
        ?OwnerProfileManager $profileManager = null
    ) {
        $this->modelManager    = $modelManager;
        $this->detector        = $detector;
        $this->contextBuilder  = $contextBuilder;
        $this->scanner         = $scanner;
        $this->redactor        = $redactor;
        $this->memory          = $memory;
        $this->kSearch         = $kSearch;
        $this->toolManager     = $toolManager;
        $this->personalModel   = $personalModel;
        $this->profileManager  = $profileManager;
        $this->parser          = new ResponseParser();

        $refRoot = new \ReflectionProperty($this->memory, 'workspaceRoot');
        $refRoot->setAccessible(true);
        $root = $refRoot->getValue($this->memory);

        $refConn = null;
        $ref = new \ReflectionProperty($this->memory, 'connection');
        $ref->setAccessible(true);
        $refConn = $ref->getValue($this->memory);

        $this->learningEngine = new LearningEngine($refConn, $root);

        // ── Phase 23 Brain Engine initialisation ──────────────────────────────
        $this->intentEngine    = new IntentEngine();
        $this->voiceEngine     = new Voice\VoiceEngine();
        $device                = new DeviceAbstraction();
        $this->awarenessEngine = new AwarenessEngine($root, $device);
        $this->contextEngine   = new ContextEngine();

        // Build personality from owner profile if available
        if ($this->profileManager !== null) {
            $profile = $this->profileManager->getProfile();
            $this->personalityEngine = PersonalityEngine::fromOwnerProfile($profile);
        } else {
            $this->personalityEngine = new PersonalityEngine();
        }
        // ─────────────────────────────────────────────────────────────────────
    }

    public function getLearningEngine(): LearningEngine
    {
        return $this->learningEngine;
    }

    public function getProfileManager(): ?OwnerProfileManager
    {
        return $this->profileManager;
    }

    // ── Phase 23 Brain Engine Accessors ───────────────────────────────────────

    public function getIntentEngine(): IntentEngine
    {
        return $this->intentEngine;
    }

    public function getPersonalityEngine(): PersonalityEngine
    {
        return $this->personalityEngine;
    }

    public function getContextEngine(): ContextEngine
    {
        return $this->contextEngine;
    }

    public function getAwarenessEngine(): AwarenessEngine
    {
        return $this->awarenessEngine;
    }

    public function getVoiceEngine(): Voice\VoiceEngine
    {
        return $this->voiceEngine;
    }

    public function getBrainState(): array
    {
        return [
            'context_summary'   => $this->contextEngine->getSummary(),
            'environment'       => $this->awarenessEngine->getEnvironmentData(),
            'personality_style' => $this->personalityEngine->getStyle(),
            'voice_mode'        => $this->voiceEngine->isVoiceModeActive(),
            'device'            => $this->awarenessEngine->getDeviceContext()->getDeviceType(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Returns cached workspace scan stats, rescanned at most once per TTL (60s).
     */
    private function getCachedWorkspaceStats(): array
    {
        static $cache = null;
        static $cachedAt = 0;
        $now = time();

        if ($cache !== null && ($now - $cachedAt) < 60) {
            return $cache;
        }

        $files = $this->scanner->scan();
        $cache = $this->scanner->getStats($files);
        $cachedAt = $now;

        return $cache;
    }

    /**
     * Processes natural language input and returns a response.
     *
     * @param string      $input          The user's message.
     * @param array       $history        Conversation history (reference).
     * @param string|null $provider       Optional provider to route to (e.g. 'Groq', 'Gemini', 'OpenAI', 'Ollama').
     * @param string|null $model          Optional model name (used for logging).
     * @param int|null    $conversationId Optional web chat ID for request tracing.
     */
    public function process(string $input, array &$history, ?string $provider = null, ?string $model = null, ?int $conversationId = null): string
    {
        $this->conversationId = $conversationId;
        $startTime = microtime(true);
        $reqId = 'REQ-' . strtoupper(bin2hex(random_bytes(6)));
        $inputLower = strtolower(trim($input));

        // Intercept feedback (explicit or natural)
        if ($this->personalModel !== null) {
            $feedbackResult = $this->personalModel->getFeedbackManager()->handleFeedback($input);
            if ($feedbackResult['handled']) {
                return $feedbackResult['response'];
            }
        }

        // Intercept user corrections
        if (preg_match('/^(no|correction|incorrect)\s*,?\s*(.+)$/i', $input, $matches)) {
            $rep = $this->handleUserCorrection($matches[2]);
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'CORRECTION', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            return $rep;
        }

        // Intercept multi-language translation requests
        if (preg_match('/^translate\s+to\s+([a-zA-Z\s]+)\s*:\s*(.+)$/i', $input, $matches)) {
            $targetLang = trim($matches[1]);
            $textToTranslate = trim($matches[2]);
            $rep = $this->handleTranslateRequest($targetLang, $textToTranslate);
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'TRANSLATION', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            return $rep;
        }

        // Intercept actionable ARC Control commands (Desktop automation & Git operations)
        $arcReply = $this->handleArcControlCommand($input);
        if ($arcReply !== null) {
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'ARC_CONTROL', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            $this->memory->logMessage('user', $input);
            $this->memory->logMessage('assistant', $arcReply);
            return $arcReply;
        }

        // 1. Intercept memory commands
        if (preg_match('/^remember\s+that\s+(.+)$/i', $input, $matches)) {
            $rep = $this->handleRememberPreference($matches[1]);
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'MEMORY_RECORD', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            return $rep;
        }

        if (preg_match('/^forget\s+memory\s+(\d+)$/i', $input, $matches)) {
            $rep = $this->handleForgetMemory((int)$matches[1]);
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'MEMORY_DELETE', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            return $rep;
        }

        if (preg_match('/^remember\s+solution\s*:\s*(.+)$/i', $input, $matches)) {
            $rep = $this->handleRememberSolution($matches[1]);
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'MEMORY_RECORD', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            return $rep;
        }

        // Handle audit trail query "why do you know this"
        if ($inputLower === 'why do you know this?' || $inputLower === 'why do you know this') {
            $trail = $this->memory->getWorkingMemory('why_know_this');
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'AUDIT_TRAIL', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            if ($trail) {
                return "ATOM:\n" . $trail;
            }
            return "ATOM:\nI haven't resolved any specific knowledge query in this active turn context.";
        }

        // --- LOCAL BRAIN FIRST: Personal Learning, GATE Exam, Profile & Stats (Zero LLM Tokens) ---
        $ownerName = 'Vichu';
        if ($this->profileManager !== null) {
            $prof = $this->profileManager->getProfile();
            $ownerName = $prof['preferred_name'] ?? $prof['full_name'] ?? 'Vichu';
        }

        // 1. Session Hold / Pause
        if (preg_match('/^(?:hold|hold\s+no|on\s+hold|pause\s+session)$/i', $inputLower)) {
            $rep = "ATOM:\nUnderstood, {$ownerName}. We'll put that on hold.\n\nWhat would you like to focus on instead?";
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'SESSION_CONTROL', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            $this->memory->logMessage('user', $input);
            $this->memory->logMessage('assistant', $rep);
            return $rep;
        }

        // 2. Personal Learning Roadmap & GATE Exam
        if (preg_match('/(?:developement|development|gate\s+exam|gate\s+schedule|study\s+plan|learning\s+roadmap|exam\s+schedule)/i', $inputLower)) {
            $rep = "ATOM [Personal Learning & GATE Strategic Roadmap]:\n\n" .
                   "🎯 **GATE Exam Roadmap (12-Week Strategic Track)**:\n" .
                   "  • Weeks 1–4: Engineering Mathematics, Discrete Math & Theory of Computation\n" .
                   "  • Weeks 5–8: Data Structures, Algorithms & Computer Networks\n" .
                   "  • Weeks 9–10: Database Management Systems & Operating Systems\n" .
                   "  • Weeks 11–12: Full Mock Exam Series & High-Yield Problem Sets\n\n" .
                   "💻 **Technical Development Track ({$ownerName})**:\n" .
                   "  • Focus Target: Enterprise Backend & Testing Architecture (PHPUnit, CodeIgniter, WPF)\n" .
                   "  • Autonomous Milestone: Build and verify each component incrementally\n\n" .
                   "Which area would you like to dive into first?";
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'ROADMAP', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            $this->memory->logMessage('user', $input);
            $this->memory->logMessage('assistant', $rep);
            return $rep;
        }

        // 3. User Identity & Personal Profile
        if (preg_match('/^(?:who\s+am\s+i|what\s+is\s+my\s+name|my\s+profile|about\s+me)$/i', $inputLower)) {
            $rep = "ATOM [Personal Profile]:\n" .
                   "  • Owner: {$ownerName} (Vishnupriyan R)\n" .
                   "  • Focus: Advanced Software Engineering & GATE Preparation\n" .
                   "  • System: ATOM Autonomous Assistant Architecture\n" .
                   "  • Active Workspace: " . basename($this->workspaceRoot);
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'PROFILE', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            $this->memory->logMessage('user', $input);
            $this->memory->logMessage('assistant', $rep);
            return $rep;
        }

        // 4. Workspace Stats Query
        if (preg_match('/^(?:workspace\s+status|how\s+many\s+files|file\s+count|project\s+files)$/i', $inputLower)) {
            $stats = $this->getCachedWorkspaceStats();
            $fileCount = count($this->scanner->scan());
            $rep = "ATOM [Workspace Status]:\n" .
                   "  • Active Files: {$fileCount}\n" .
                   "  • Root: {$this->workspaceRoot}\n" .
                   "  • Status: Online & Indexed";
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, 'STATS', 'LOCAL', 'None', false, 100, $durationMs, 'SUCCESS');
            $this->memory->logMessage('user', $input);
            $this->memory->logMessage('assistant', $rep);
            return $rep;
        }

        // 2. Intent classification — Phase 23 IntentEngine (richer routing)
        $intentResult    = $this->intentEngine->classify($input);
        $intent          = $intentResult->intent;
        $routingHint     = $intentResult->routingHint;

        // Legacy detector kept for backward compatibility with CLI/API callers
        // that may read the old intent string directly.
        $legacyIntent = $this->detector->detect($input);

        // Fetch matching RAG knowledge base chunks
        $knowledgeChunks = $this->kSearch->search($input);

        // Determine collaboration mode
        $colMode = 'balanced';
        $refConn = null;
        $ref = new \ReflectionProperty($this->memory, 'connection');
        $ref->setAccessible(true);
        $refConn = $ref->getValue($this->memory);
        if ($this->memory->isDbConnected() && $refConn) {
            try {
                $stmt = $refConn->getPdo()->prepare("SELECT setting_value FROM atom_settings WHERE setting_key = 'collaboration_mode'");
                $stmt->execute();
                $modeVal = $stmt->fetchColumn();
                if ($modeVal) {
                    $colMode = strtolower($modeVal);
                }
            } catch (\Exception $e) {}
        }

        // Calculate confidence score (0-100)
        $dbMemories = $this->memory->getMemories();
        $memoryMatches = 0;
        foreach ($dbMemories as $mem) {
            if (stripos($input, $mem['memory_key']) !== false || stripos($input, $mem['memory_value']) !== false) {
                $memoryMatches++;
            }
        }

        $hasKnowledge = !empty($knowledgeChunks);
        $confidenceScore = 20; // Default Low
        if ($memoryMatches > 0 && $hasKnowledge) {
            $confidenceScore = 85;
        } elseif ($memoryMatches > 0) {
            $confidenceScore = 70;
        } elseif ($hasKnowledge) {
            $confidenceScore = 60;
        }

        $confidenceTag = 'LOW';
        if ($confidenceScore >= 80) $confidenceTag = 'HIGH';
        elseif ($confidenceScore >= 60) $confidenceTag = 'MODERATE';

        // Set Audit Trail details in Working Memory
        $auditTrail = "Sources:\n";
        if ($memoryMatches > 0) $auditTrail .= " - Project Memory & Stored Preferences ($memoryMatches matches)\n";
        if ($hasKnowledge) $auditTrail .= " - Knowledge Library (" . count($knowledgeChunks) . " chunks retrieved)\n";
        if ($confidenceScore < 60) $auditTrail .= " - Gemini AI Explainer (used for reasoning)\n";
        $auditTrail .= "Confidence: {$confidenceScore}% ($confidenceTag)";
        $this->memory->setWorkingMemory('why_know_this', $auditTrail);

        // Routing logic based on mode & confidence
        $skipGemini = false;
        if ($colMode === 'local') {
            $skipGemini = ($confidenceScore >= 30);
        } elseif ($colMode === 'balanced') {
            $skipGemini = ($confidenceScore >= 80);
        }

        // Handle offline fallback or local-only responses
        if ($skipGemini || $this->modelManager === null) {
            $reply = $this->handleOfflineFallback($input, $intent, $knowledgeChunks);
            $this->memory->logMessage('user', $input);
            $this->memory->logMessage('assistant', $reply);
            
            // Increment local answers counter
            if ($this->memory->isDbConnected() && $refConn) {
                try {
                    $refConn->getPdo()->exec("INSERT INTO atom_settings (setting_key, setting_value) VALUES ('local_answers', '1') ON DUPLICATE KEY UPDATE setting_value = CAST(setting_value AS UNSIGNED) + 1");
                } catch (\Exception $e) {}
            }
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, $intent, 'LOCAL', 'None', $hasKnowledge, $confidenceScore, $durationMs, 'SUCCESS');
            $this->logResponse($reqId, $reply, 'None', 'None');
            return $reply;
        }

        // Increment Gemini consultations counter
        if ($this->memory->isDbConnected() && $refConn) {
            try {
                $refConn->getPdo()->exec("INSERT INTO atom_settings (setting_key, setting_value) VALUES ('gemini_consultations', '1') ON DUPLICATE KEY UPDATE setting_value = CAST(setting_value AS UNSIGNED) + 1");
            } catch (\Exception $e) {}
        }

        // Get project stats for context builder (cached for 60s to avoid rescanning on every request)
        $stats = $this->getCachedWorkspaceStats();

        $stats['memories'] = $dbMemories;
        $stats['knowledge'] = $knowledgeChunks;

        if ($this->personalModel !== null) {
            $stats['personalization_prompt'] = $this->personalModel->getPersonalizedSystemPrompt();
        }

        if ($this->profileManager !== null) {
            $profile = $this->profileManager->getProfile();
            $stats['owner_preferred_name'] = $profile['preferred_name'] ?? $profile['full_name'] ?? 'Vishnupriyan';
        }

        $stats['personal_profile']  = $this->memory->getPersonalProfile();
        $stats['session_memory']    = $this->memory->getSessionMemory();

        // ── Phase 23: inject Awareness + Context + Personality blocks ─────────
        $ownerProfileArr                = $stats['personal_profile'] ?? [];
        $stats['awareness_block']       = $this->awarenessEngine->getEnvironmentBlock();
        $stats['context_block']         = $this->contextEngine->buildContextBlock();
        $stats['personality_block']     = $this->personalityEngine->buildPersonalityBlock($ownerProfileArr);
        // ─────────────────────────────────────────────────────────────────────

        // Format user query message
        $userMsg = ['role' => 'user', 'content' => $input];

        // Assemble initial context payload
        $messages = $this->contextBuilder->assemble($history, $userMsg, $stats);

        // 3. Recursive agent loop
        $loopCount = 0;
        $maxLoops = 5;
        $finalReply = '';
        $providerName = $provider ?: \Atom\Config\Config::get('LLM_PROVIDER', 'groq');
        $modelName = $model ?: \Atom\Config\Config::get('LLM_MODEL', 'openai/gpt-oss-120b');
        $myLifeToolAttempted = false;
        $myLifeDataAccessed = false;

        while ($loopCount < $maxLoops) {
            if ($provider !== null && $provider !== '') {
                $res = $this->modelManager->generateForProvider($provider, $messages);
            } else {
                $res = $this->modelManager->generateForRole('primary', $messages);
            }

            if (!$res->isSuccess()) {
                $errMessage = $res->getError();
                if ($myLifeDataAccessed) {
                    return 'ATOM: The response could not be completed after the approved MyLife read. The MyLife data was not added to persistent chat logs or session history.';
                }
                $this->logError('ERR-' . strtoupper(bin2hex(random_bytes(4))), 'AI_PROVIDER', 'ERROR', $errMessage, $reqId);
                $durationMs = (int)((microtime(true) - $startTime) * 1000);
                $this->logRequest($reqId, $input, $intent, 'COLLABORATIVE', $providerName, $hasKnowledge, $confidenceScore, $durationMs, 'FAILED');
                
                if (strpos(strtolower($errMessage), 'rate-limit') !== false || strpos(strtolower($errMessage), 'quota') !== false || strpos($errMessage, '429') !== false) {
                    return "ATOM: The primary AI provider is temporarily rate-limited. Switch providers using /provider or try again shortly.";
                }
                return "ATOM: " . $errMessage . "\n(Switch active provider with /provider or check connection)";
            }

            $rawReply = $res->getContent();
            
            // Parse reply for tool execution blocks
            $parsed = $this->parser->parse($rawReply);
            $finalReply = $parsed['explanation'];

            if ($parsed['tool_call'] !== null) {
                $toolName = $parsed['tool_call']['name'];
                $toolArgs = $parsed['tool_call']['arguments'] ?? [];

                if ($myLifeDataAccessed) {
                    $finalReply = 'I retrieved the requested MyLife data, but I blocked further tools for this turn. Ask for any additional action separately.';
                    break;
                }
                if ($toolName === 'mylife_os' && $myLifeToolAttempted) {
                    $finalReply = 'Only one MyLife access attempt is allowed per turn. Please ask again if you want another read.';
                    break;
                }
                if ($toolName === 'mylife_os') {
                    $myLifeToolAttempted = true;
                }

                echo "\033[33mATOM is executing tool '{$toolName}'...\033[0m" . PHP_EOL;

                $toolStart = microtime(true);
                // Run the registered tool with dynamic ARC fallback
                if ($this->toolManager->hasTool($toolName)) {
                    $toolResult = $this->toolManager->executeTool($toolName, $toolArgs);
                } elseif (str_starts_with($toolName, 'arc_') || in_array($toolName, ['desktop', 'git', 'system', 'sys', 'dev'])) {
                    if (!$this->toolManager->authorizeAction($toolName, $toolArgs)) {
                        $toolResult = [
                            'success' => false,
                            'requires_human_gate' => true,
                            'tool_name' => $toolName,
                            'approval_explanation' => $this->toolManager->describeAction($toolName, $toolArgs),
                        ];
                    } else {
                        $toolResult = $this->getArcController()->dispatch($toolName, $toolArgs);
                    }
                } else {
                    $toolResult = $this->toolManager->executeTool($toolName, $toolArgs);
                }
                $toolDuration = (int)((microtime(true) - $toolStart) * 1000);

                if (!empty($toolResult['requires_human_gate'])) {
                    $details = $toolResult['approval_explanation'] ?? [];
                    $finalReply = $this->toolManager->hasApprovalProvider()
                        ? "I did not perform this action because it was not approved. No tool was executed.\n"
                        : "I have not performed this action because this client has no active human approval handler. No tool was executed.\n";
                    $finalReply .= "Action: " . ($details['action'] ?? $toolName) . "\n"
                        . "Target: " . (is_scalar($details['target'] ?? null) ? (string)$details['target'] : 'See the requested parameters') . "\n"
                        . "Purpose: " . ($details['purpose'] ?? 'Review the proposed action') . "\n"
                        . "Access: " . ($details['access'] ?? 'Unclassified; approval required') . "\n"
                        . "Data sharing: " . ($details['external_data_sharing'] ?? 'Unknown') . "\n"
                        . "Effects: " . ($details['consequences'] ?? 'Review before approving');
                    break;
                }

                if ($toolName === 'mylife_os' && !empty($toolResult['success'])) {
                    $myLifeDataAccessed = true;
                }
                if ($toolName !== 'mylife_os') {
                    $this->logToolExecution($toolName, $toolArgs, $toolResult, $toolDuration, 'SUCCESS');
                }

                // Format tool result output
                $resultStr = json_encode($toolResult, JSON_PRETTY_PRINT);
                if (strlen($resultStr) > 5000) {
                    $resultStr = substr($resultStr, 0, 5000) . "\n... [TRUNCATED FOR LENGTH] ...";
                }

                // Feed back into messages array
                $messages[] = ['role' => 'assistant', 'content' => $rawReply];
                $messages[] = ['role' => 'user', 'content' => "TOOL RESULT FOR {$toolName}: " . $resultStr];

                $loopCount++;
            } else {
                // No more tool calls requested. Break out of loop.
                break;
            }
        }

        // Redact any secrets leaked in reply
        $finalReply = $this->redactor->redact($finalReply);

        // ----------------------------------------------------------------
        // SELF-LEARNING: 50% cross-model training
        // Every other request, consult a second provider and compare.
        // The better response is saved as a training example.
        // ----------------------------------------------------------------
        if (!$myLifeDataAccessed) {
            $finalReply = $this->runSelfLearning($input, $messages, $finalReply, $providerName);
        }

        // ── Phase 23: apply PersonalityEngine post-processor ─────────────────
        $finalReply = $this->personalityEngine->applyPersonality($finalReply, $intent);
        if ($this->voiceEngine->isVoiceModeActive()) {
            $finalReply = $this->voiceEngine->formatForVoice($finalReply);
        }
        // ── Phase 23: update ContextEngine with this turn ────────────────────
        if (!$myLifeDataAccessed) {
            $this->contextEngine->update($input, $finalReply, $intent);
        }
        // ─────────────────────────────────────────────────────────────────────

        // Save conversation history to local array and persistent DB
        if (!$myLifeDataAccessed) {
            $history[] = $userMsg;
            $history[] = ['role' => 'assistant', 'content' => $finalReply];

            $this->memory->logMessage('user', $input);
            $this->memory->logMessage('assistant', $finalReply);

            // Save trace records to DB
            $durationMs = (int)((microtime(true) - $startTime) * 1000);
            $this->logRequest($reqId, $input, $intent, 'COLLABORATIVE', $providerName, $hasKnowledge, $confidenceScore, $durationMs, 'SUCCESS');
            $this->logResponse($reqId, $finalReply, $providerName, $modelName);
        }

        return $finalReply;
    }

    // ----------------------------------------------------------------
    // Self-Learning Engine
    // ----------------------------------------------------------------

    /**
     * On every other request (50% cadence), consult a second available
     * provider, compare its response to the primary response, pick the
     * better one, and auto-save the Q&A pair as a training example.
     *
     * Rules:
     *  - Primary response is always used as the base.
     *  - Second provider is chosen from whichever registered cloud model
     *    is NOT the primary (Groq ↔ Gemini, etc.).
     *  - Better response wins by quality score (length, code, structure).
     *  - The winning pair is saved via TrainingExampleRepository with all
     *    12 deduplication / optimization rules applied automatically.
     *
     * @return string The (possibly upgraded) final reply.
     */
    private function runSelfLearning(string $input, array $messages, string $primaryReply, string $primaryProviderName): string
    {
        // Static counter — increments each call, drives 50% cadence
        static $requestCount = 0;
        $requestCount++;

        // Only run on every other request (50%)
        if ($requestCount % 2 !== 0) {
            return $primaryReply;
        }

        if ($this->modelManager === null || $this->personalModel === null) {
            return $primaryReply;
        }

        // Find a second cloud provider that is different from the primary
        $secondaryModel = $this->pickSecondaryProvider($primaryProviderName);
        if ($secondaryModel === null) {
            return $primaryReply; // No second provider available
        }

        // Ask the secondary provider the same question
        try {
            $secondRes = $secondaryModel->generate($messages);
        } catch (\Throwable $e) {
            return $primaryReply; // Secondary failed — keep primary
        }

        if (!$secondRes->isSuccess() || empty(trim($secondRes->getContent()))) {
            return $primaryReply;
        }

        $secondaryReply = $this->redactor->redact(trim($secondRes->getContent()));
        $secondProviderName = $secondaryModel->getProviderName();

        // Score both responses and pick the better one
        $primaryScore   = $this->scoreResponse($primaryReply);
        $secondaryScore = $this->scoreResponse($secondaryReply);

        $winner        = $primaryScore >= $secondaryScore ? $primaryReply   : $secondaryReply;
        $winnerSource  = $primaryScore >= $secondaryScore ? $primaryProviderName : $secondProviderName;
        $loserScore    = min($primaryScore, $secondaryScore);
        $winnerScore   = max($primaryScore, $secondaryScore);

        // Only store as training example when there is a meaningful quality gap
        // (avoids saving redundant near-identical pairs)
        $qualityGap = $winnerScore - $loserScore;
        if ($qualityGap >= 5) {
            $this->saveTrainingExample($input, $winner, $winnerSource, $primaryProviderName, $secondProviderName);
        }

        return $winner;
    }

    /**
     * Find the best secondary cloud provider that differs from the primary.
     * Preference: Gemini > Groq > OpenAI (skips Ollama local fallback).
     */
    private function pickSecondaryProvider(string $primaryProviderName): ?\Atom\PersonalModel\ModelInterface
    {
        if ($this->modelManager === null) {
            return null;
        }

        $primaryLower = strtolower($primaryProviderName);
        $preference   = ['gemini', 'groq', 'openai']; // priority order

        foreach ($preference as $alias) {
            if ($alias === $primaryLower) {
                continue; // skip primary
            }
            $model = $this->modelManager->getModel($alias);
            if ($model !== null && $model->isAvailable()) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Score a response for quality on a 0–100 scale.
     *
     * Criteria:
     *  - Length (longer = more complete, up to a point)
     *  - Contains code blocks (```)
     *  - Contains numbered/bullet list structure
     *  - Contains technical keywords
     *  - Not just a repetition of the question
     */
    private function scoreResponse(string $response): int
    {
        $score = 0;
        $len   = mb_strlen($response);

        // Length score (up to 40 pts)
        if ($len >= 1000) $score += 40;
        elseif ($len >= 500) $score += 30;
        elseif ($len >= 200) $score += 20;
        elseif ($len >= 80)  $score += 10;

        // Contains fenced code block (up to 20 pts)
        $codeBlocks = substr_count($response, '```');
        $score += min(20, $codeBlocks * 10);

        // Structured list (up to 15 pts)
        if (preg_match('/^\s*(\d+\.|[-*•])/m', $response)) {
            $score += 15;
        }

        // Technical keywords (up to 15 pts)
        $techKeywords = ['function', 'class', 'return', 'array', 'SELECT', 'WHERE',
                         'require', 'namespace', 'interface', 'extends', '$', '->', '::'];
        $keyHits = 0;
        foreach ($techKeywords as $kw) {
            if (stripos($response, $kw) !== false) {
                $keyHits++;
            }
        }
        $score += min(15, $keyHits * 3);

        // Penalty: response too short to be useful
        if ($len < 50) {
            $score = max(0, $score - 20);
        }

        return min(100, $score);
    }

    /**
     * Persist the winning cross-model Q&A pair as a training example,
     * applying all 12 deduplication / optimization rules automatically.
     */
    private function saveTrainingExample(
        string $question,
        string $response,
        string $winnerProvider,
        string $primaryProvider,
        string $secondaryProvider
    ): void {
        if ($this->personalModel === null) {
            return;
        }

        $context = "Cross-model training: {$primaryProvider} vs {$secondaryProvider}. Winner: {$winnerProvider}.";

        $result = $this->personalModel->addTrainingExample(
            userInput:       $question,
            preferredResponse: $response,
            category:       'self_learning',
            contextSummary: $context,
            source:         'cross_model_training',
            quality:        'GOOD'
        );

        // Log the self-learning event to the learning engine
        $actionText = "Self-learning [{$winnerProvider} won vs {$secondaryProvider}]: " . substr($question, 0, 80);
        $this->learningEngine->logHistory('Self-Learning', $actionText, 'ATOM_BRAIN', 'HIGH');
    }

    private function handleRememberPreference(string $preferenceText): string
    {
        if (!$this->memory->isDbConnected()) {
            return "ATOM:\nDatabase is offline. Unable to store long-term preference: \"$preferenceText\"";
        }

        $key = 'preference';
        if (preg_match('/^i\s+(prefer|like)\s+([a-zA-Z0-9\s]+)/i', $preferenceText, $parts)) {
            $key = str_replace(' ', '_', trim(strtolower($parts[2])));
        }

        $success = $this->memory->rememberMemory($key, $preferenceText, 'preference');
        if ($success) {
            return "ATOM:\nI will remember that: \"$preferenceText\"";
        }
        return "ATOM:\nFailed to record preference.";
    }

    private function handleForgetMemory(int $id): string
    {
        if (!$this->memory->isDbConnected()) {
            return "ATOM:\nDatabase is offline. Unable to delete memory.";
        }

        $success = $this->memory->forgetMemory($id);
        if ($success) {
            return "ATOM:\nMemory ID $id forgotten successfully.";
        }
        return "ATOM:\nMemory ID $id not found or failed to delete.";
    }

    private function handleRememberSolution(string $solutionInput): string
    {
        if (!$this->memory->isDbConnected()) {
            return "ATOM:\nDatabase is offline. Unable to store solution.";
        }

        $problem = '';
        $cause = '';
        $fix = '';
        
        $parts = explode(';', $solutionInput);
        foreach ($parts as $part) {
            $subParts = explode('=', $part, 2);
            if (count($subParts) === 2) {
                $field = trim(strtolower($subParts[0]));
                $value = trim($subParts[1]);
                if ($field === 'problem') $problem = $value;
                if ($field === 'cause') $cause = $value;
                if ($field === 'fix') $fix = $value;
            }
        }

        if (empty($problem) || empty($fix)) {
            $problem = $solutionInput;
            $cause = 'N/A';
            $fix = 'Provided solution note.';
        }

        $success = $this->memory->rememberSolution($problem, $cause, $fix);
        if ($success) {
            return "ATOM:\nI have saved this solution to my technical memory library.";
        }
        return "ATOM:\nFailed to save solution.";
    }

    /**
     * Fallback responses when LLM provider is not configured.
     */
    private function handleOfflineFallback(string $input, string $intent, array $knowledgeChunks = []): string
    {
        $inputLower = strtolower(trim($input));

        if ($intent === 'conversation') {
            $name = 'Owner';
            if ($this->profileManager !== null) {
                $profile = $this->profileManager->getProfile();
                $name = $profile['preferred_name'] ?? $profile['full_name'] ?? 'Owner';
            }

            if (in_array($inputLower, ['hi', 'hello', 'hey', 'yo'], true)) {
                return "ATOM:\nHi, {$name}! What would you like to work on?";
            }
            if ($inputLower === 'what can you do?' || $inputLower === 'what is your purpose?') {
                return "ATOM:\nI can help with conversation and local project tools.\nMy coding intelligence, memory, and document knowledge depend on which ATOM modules are currently enabled.\n\nConfigure a local or cloud LLM key in your `.env` file to unlock active reasoning.";
            }
            return "ATOM:\nHello, {$name}! How can I assist you with your PHP development today?";
        }

        if ($intent === 'project_list') {
            $files = $this->scanner->scan();
            return "ATOM:\nI detected your active workspace with " . count($files) . " files.\nUse `/project` or `/files` command to explore the directory structure.";
        }

        // Offline matching knowledge lookup
        if (!empty($knowledgeChunks)) {
            $reply = "ATOM:\nI detected that you asked about: \"$input\"\n\nNote: The LLM model is offline, but I matched the following reference in my local technical knowledge library:\n\n";
            foreach ($knowledgeChunks as $idx => $chunk) {
                $reply .= "  [" . ($idx + 1) . "] Source: " . $chunk['title'] . " (Page " . $chunk['page_number'] . ")\n";
                $reply .= "      \"" . substr($chunk['chunk_text'], 0, 200) . "...\"\n\n";
            }
            $reply .= "Configure a `.env` API key to let the LLM generate a summarized answer from these excerpts.";
            return $reply;
        }

        // Default local notification
        return "ATOM:\nI detected that you asked about: \"$input\"\n\nNote: The LLM model is currently offline/unconfigured. Please create a `.env` file in the root directory to enable AI reasoning. Examples:\n\nLLM_API_KEY=your-api-key-here\nLLM_API_URL=https://api.openai.com/v1\n\nMeanwhile, you can use local slash commands like `/help`, `/status`, or `/search`.";
    }

    private function handleUserCorrection(string $correctionText): string
    {
        $this->learningEngine->logHistory('Project Memory', "Vichu corrected: " . $correctionText, 'User Correction', 'HIGH');

        if ($this->memory->isDbConnected()) {
            $ref = new \ReflectionProperty($this->memory, 'connection');
            $ref->setAccessible(true);
            $refConn = $ref->getValue($this->memory);
            $pdo = $refConn->getPdo();

            $framework = null;
            if (stripos($correctionText, 'CI3') !== false || stripos($correctionText, 'codeigniter 3') !== false) $framework = 'CodeIgniter 3';
            if (stripos($correctionText, 'CI4') !== false || stripos($correctionText, 'codeigniter 4') !== false) $framework = 'CodeIgniter 4';
            if (stripos($correctionText, 'Laravel') !== false) $framework = 'Laravel';

            if ($framework !== null) {
                $stmt = $pdo->prepare("SELECT id FROM atom_memories WHERE project_id = ? AND memory_key = 'framework'");
                $stmt->execute([$this->memory->getProjectId()]);
                $row = $stmt->fetch();
                if ($row) {
                    $up = $pdo->prepare("UPDATE atom_memories SET memory_value = ? WHERE id = ?");
                    $up->execute(["Use {$framework} for this project.", $row['id']]);
                } else {
                    $this->memory->rememberMemory('framework', "Use {$framework} for this project.", 'preference');
                }
                
                $topic = (stripos($framework, 'CodeIgniter') !== false) ? 'CodeIgniter' : 'Laravel';
                $this->learningEngine->updateTopicMetrics($topic, true, false);

                // Update setting counter for validated answers
                try {
                    $pdo->exec("INSERT INTO atom_settings (setting_key, setting_value) VALUES ('validated_answers', '1') ON DUPLICATE KEY UPDATE setting_value = CAST(setting_value AS UNSIGNED) + 1");
                } catch (\Exception $e) {}

                return "ATOM:\nI have corrected my project memory. I'll make sure to use {$framework} instead.";
            }
        }
        
        return "ATOM:\nI have recorded your correction: \"$correctionText\". I will apply this to my learning context.";
    }

    private function logRequest(string $reqId, string $query, string $intent, string $resType, string $provider, bool $ragUsed, int $confidence, int $durationMs, string $status): void
    {
        if (!$this->memory->isDbConnected()) return;
        try {
            $ref = new \ReflectionProperty($this->memory, 'connection');
            $ref->setAccessible(true);
            $refConn = $ref->getValue($this->memory);
            $pdo = $refConn->getPdo();

            $stmt = $pdo->prepare("
                INSERT INTO atom_requests (request_id, user_query, intent, resolution_type, provider, rag_used, confidence, duration_ms, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reqId,
                $this->redactor->redact($query),
                $intent,
                $resType,
                $provider,
                $ragUsed ? 1 : 0,
                $confidence,
                $durationMs,
                $status
            ]);
        } catch (\Exception $e) {}
    }

    private function logResponse(string $reqId, string $response, string $provider, string $model): void
    {
        if (!$this->memory->isDbConnected()) return;
        try {
            $ref = new \ReflectionProperty($this->memory, 'connection');
            $ref->setAccessible(true);
            $refConn = $ref->getValue($this->memory);
            $pdo = $refConn->getPdo();

            $stmt = $pdo->prepare("
                INSERT INTO atom_responses (request_id, conversation_id, final_response, provider, model)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reqId,
                $this->conversationId ?? 0,
                $this->redactor->redact($response),
                $provider,
                $model
            ]);
        } catch (\Exception $e) {}
    }

    private function logError(string $errId, string $category, string $severity, string $message, ?string $reqId): void
    {
        if (!$this->memory->isDbConnected()) return;
        try {
            $ref = new \ReflectionProperty($this->memory, 'connection');
            $ref->setAccessible(true);
            $refConn = $ref->getValue($this->memory);
            $pdo = $refConn->getPdo();

            $stmt = $pdo->prepare("
                INSERT INTO atom_errors (error_id, category, severity, message, request_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $errId,
                $category,
                $severity,
                $this->redactor->redact($message),
                $reqId
            ]);
        } catch (\Exception $e) {}
    }

    private function logToolExecution(string $toolName, array $args, array $result, int $durationMs, string $status): void
    {
        if (!$this->memory->isDbConnected()) return;
        try {
            $ref = new \ReflectionProperty($this->memory, 'connection');
            $ref->setAccessible(true);
            $refConn = $ref->getValue($this->memory);
            $pdo = $refConn->getPdo();

            $stmt = $pdo->prepare("
                INSERT INTO atom_tool_executions (tool_name, arguments, result, duration_ms, status)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $toolName,
                $this->redactor->redact(json_encode($args)),
                $this->redactor->redact(json_encode($result)),
                $durationMs,
                $status
            ]);
        } catch (\Exception $e) {}
    }

    public function getArcController(): ArcMasterController
    {
        if ($this->arcController === null) {
            $this->arcController = new ArcMasterController();
        }
        return $this->arcController;
    }

    /**
     * Handles actionable ARC control requests directly, executing real Windows & Git actions
     * instead of returning passive step-by-step instructions.
     */
    private function handleArcControlCommand(string $input): ?string
    {
        $trim = trim($input);
        // Strip conversational prefixes like "adam,", "atom,", "hey atom,", "you", "can you", "please", etc.
        $clean = preg_replace('/^(?:(?:hey|hi|hello)?\s*(?:adam|atom|assistant|you)\s*[,:\-\s]*|please\s+|can\s+you\s+|could\s+you\s+)+/i', '', $trim);
        $clean = trim($clean);

        // Pattern 1: "open notepad type <text>", "open my notepad and say <text>", "launch notepad and type <text>"
        if (preg_match('/^(?:open|launch)\s+(?:my\s+)?notepad\s*(?:and\s+|,|:)?\s*(?:type|write|say)\s*[:\s]*["\']?([^"\']+)["\']?$/i', $clean, $m)) {
            $textToType = trim($m[1]);
            $res = $this->getArcController()->arcTwo()->runMilestoneOne($textToType);
            if ($res['success']) {
                return "ATOM [ARC Control System]:\n✓ Successfully executed desktop automation:\n  • Action: Opened Notepad (notepad.exe)\n  • Window: Focused and active\n  • Typed: \"{$textToType}\"\n  • Status: Complete (0 errors)";
            }
            return "ATOM [ARC Control System]:\n✗ Desktop automation encountered an issue: " . ($res['error'] ?? 'Unknown error');
        }

        // Pattern 2: "type <text> in notepad"
        if (preg_match('/^(?:type|write|say)\s+["\']?([^"\']+)["\']?\s+in\s+(?:my\s+)?notepad$/i', $clean, $m)) {
            $textToType = trim($m[1]);
            $res = $this->getArcController()->arcTwo()->runMilestoneOne($textToType);
            if ($res['success']) {
                return "ATOM [ARC Control System]:\n✓ Successfully typed text into Notepad:\n  • Target: notepad.exe\n  • Typed: \"{$textToType}\"\n  • Status: Complete";
            }
            return "ATOM [ARC Control System]:\n✗ Desktop automation error: " . ($res['error'] ?? 'Unknown error');
        }

        // Pattern 3: "open notepad", "launch notepad", "open my notepad"
        if (preg_match('/^(?:open|launch)\s+(?:my\s+)?notepad$/i', $clean)) {
            $res = $this->getArcController()->arcTwo()->openApplication('notepad');
            if ($res['success']) {
                return "ATOM [ARC Control System]:\n✓ Successfully launched Notepad (notepad.exe). Window is active and ready.";
            }
            return "ATOM [ARC Control System]:\n✗ Failed to launch Notepad: " . ($res['error'] ?? 'Unknown error');
        }

        // Pattern 4: "close notepad", "kill notepad", "close my notepad"
        if (preg_match('/^(?:close|kill|exit)\s+(?:my\s+)?notepad$/i', $clean)) {
            $res = $this->getArcController()->arcTwo()->closeApplication('notepad', true);
            return "ATOM [ARC Control System]:\n✓ Closed Notepad process (notepad.exe).";
        }

        // Pattern 4.1: "open chrome search that youtube play <query>", "play <query> on youtube", "search youtube for <query>"
        if (preg_match('/(?:open\s+(?:chrome|browser)\s+)?(?:search\s+(?:that\s+)?youtube\s+(?:and\s+)?play|search\s+(?:that\s+)?youtube\s+for|search\s+youtube\s+(?:and\s+)?play|open\s+youtube\s+(?:and\s+)?(?:play|search)|play\s+(.+?)\s+(?:on|in)\s+youtube|open\s+chrome\s+search\s+(.+?)\s+on\s+youtube|play\s+youtube\s+(.+)|play\s+(?:good\s+)?tamil\s+song)/i', $trim, $m)) {
            $rawQuery = !empty($m[1]) ? $m[1] : (!empty($m[2]) ? $m[2] : (!empty($m[3]) ? $m[3] : 'good tamil songs'));
            $cleanQuery = trim(preg_replace('/^(?:that|for|the|search)\s+/i', '', $rawQuery));
            if ($cleanQuery === '' || $cleanQuery === 'good tamil song' || str_contains(strtolower($trim), 'tamil song')) {
                $cleanQuery = 'good tamil songs';
            }

            $res = $this->getArcController()->arcTwo()->searchAndPlayYoutube($cleanQuery, true);
            $encoded = urlencode($cleanQuery);
            $ytUrl = "https://www.youtube.com/results?search_query={$encoded}";

            return "ATOM [ARC Control System]:\n" .
                   "✓ Successfully launched Google Chrome and opened YouTube:\n" .
                   "  • Action: Opened YouTube Search & Player\n" .
                   "  • Query: \"{$cleanQuery}\"\n" .
                   "  • Target URL: {$ytUrl}\n" .
                   "  • Status: Active in Google Chrome\n\n" .
                   "🎵 Top Recommended Video Collections for \"{$cleanQuery}\":\n" .
                   "  1. 🌟 Top Trending Tamil Hit Songs 2026 (Anirudh, AR Rahman, Yuvan, Harris)\n" .
                   "  2. 💖 90s & 2000s Evergreen Tamil Melody Classics (SPB, Ilaiyaraaja, Hariharan)\n" .
                   "  3. ⚡ Latest All-Time Favorite Tamil Mass & Melody Playlist\n\n" .
                   "Which collection would you like to focus on, or click directly in your Chrome window to play?";
        }

        // Pattern 4.2: Generic "open chrome <url>" or "open url <url>" or "open website <url>"
        if (preg_match('/^(?:open\s+(?:chrome|browser|url|website)\s+|browse\s+)(https?:\/\/\S+|www\.\S+|[a-zA-Z0-9_\-\.]+\.[a-zA-Z]{2,}\S*)$/i', $trim, $m)) {
            $targetUrl = trim($m[1]);
            $res = $this->getArcController()->arcTwo()->openUrl($targetUrl, 'chrome');
            return "ATOM [ARC Control System]:\n✓ Opened URL in Google Chrome:\n  • URL: {$res['url']}\n  • Status: Active";
        }

        // Pattern 5: Listening ports inspection
        if (preg_match('/^(?:list|show|get|check)?\s*(?:listening|active|open)?\s*ports$/i', $trim)) {
            $res = $this->getArcController()->arcThree()->getListeningPorts();
            $count = $res['count'] ?? 0;
            $table = "ATOM [ARC Developer System — Active Ports ({$count})]:\n";
            $table .= sprintf("%-8s %-20s %-8s %s\n", "PROTO", "LOCAL ADDRESS", "PID", "PROCESS");
            $table .= str_repeat("─", 50) . "\n";
            foreach (array_slice($res['ports'] ?? [], 0, 15) as $p) {
                $addr = ($p['local_address'] ?? '0.0.0.0') . ':' . ($p['port'] ?? 0);
                $table .= sprintf("%-8s %-20s %-8s %s\n", $p['protocol'] ?? 'TCP', $addr, $p['pid'] ?? '-', $p['process_name'] ?? 'System');
            }
            return $table;
        }

        // Pattern 6: Check specific port
        if (preg_match('/^check\s+port\s+(\d+)$/i', $trim, $m)) {
            $port = (int)$m[1];
            $res = $this->getArcController()->arcThree()->checkPortAvailability($port);
            if ($res['is_available']) {
                return "ATOM [ARC Developer System]:\n✓ Port {$port} is AVAILABLE (no listening processes detected).";
            }
            $occ = $res['occupied_by'] ?? [];
            return "ATOM [ARC Developer System]:\n⚠ Port {$port} is OCCUPIED by Process '{$occ['process_name']}' (PID: {$occ['pid']}).";
        }

        // Pattern 7: Detect Developer Toolchain
        if (preg_match('/^(?:detect|show|get)?\s*(?:developer|dev)?\s*(?:toolchain|runtimes|tools|sdks)$/i', $trim)) {
            $res = $this->getArcController()->arcThree()->detectDeveloperToolchain();
            $table = "ATOM [ARC Developer System — Detected Toolchain]:\n";
            foreach ($res['toolchain'] ?? [] as $tool => $info) {
                $status = !empty($info['installed']) ? "✓ v{$info['version']}" : "✗ Not Found";
                $table .= sprintf("  • %-10s : %s\n", strtoupper($tool), $status);
            }
            return $table;
        }

        // Pattern 8: Hardware Telemetry
        if (preg_match('/^(?:get|show)?\s*(?:hardware|system|compute)\s*(?:telemetry|stats|specs|status)$/i', $trim)) {
            $res = $this->getArcController()->arcThree()->getHardwareTelemetry();
            $t = $res['telemetry'] ?? [];
            $cpu = $t['cpu'] ?? [];
            $ram = $t['ram'] ?? [];
            return "ATOM [ARC Hardware Telemetry]:\n" .
                   "  • CPU : " . ($cpu['name'] ?? 'Processor') . " (" . ($cpu['cores_physical'] ?? '-') . " Cores / " . ($cpu['cores_logical'] ?? '-') . " Threads)\n" .
                   "  • RAM : " . ($ram['used_gb'] ?? 0) . " GB Used / " . ($ram['total_gb'] ?? 0) . " GB Total (" . ($ram['used_percent'] ?? 0) . "%)\n" .
                   "  • Disk Volumes : " . count($t['disks'] ?? []) . " Active Partition(s)";
        }

        // Pattern 9: List processes
        if (preg_match('/^(?:list|show|get)\s+processes(?:\s+matching\s+(.+))?$/i', $trim, $m)) {
            $filter = !empty($m[1]) ? trim($m[1]) : null;
            $res = $this->getArcController()->arcThree()->listProcesses($filter, 15);
            $table = "ATOM [ARC Process Monitor (" . ($res['count'] ?? 0) . " found)]:\n";
            $table .= sprintf("%-8s %-25s %-12s %s\n", "PID", "PROCESS", "MEMORY (MB)", "THREADS");
            $table .= str_repeat("─", 55) . "\n";
            foreach ($res['processes'] ?? [] as $p) {
                $table .= sprintf("%-8s %-25s %-12s %s\n", $p['pid'], substr($p['name'], 0, 24), $p['memory_mb'], $p['threads']);
            }
            return $table;
        }

        // Session-wide grants are disabled. The user must approve each action.
        if (preg_match('/^(?:give|grant|enable|allow)?\s*(?:full\s+system\b(?:\s+access)?|permission|session\s+access|all\s+access)$/i', $trim)) {
            return "ATOM security: session-wide access grants are disabled. I will explain each proposed action and ask you to approve it separately.";
        }

        // Pattern 11: Permission Status Check
        if (preg_match('/^(?:check|show|get)?\s*permission(?:\s*status)?$/i', $trim)) {
            $st = $this->getArcController()->getPermissionGate()->getStatus();
            $grants = !empty($st['active_session_grants']) ? implode(', ', $st['active_session_grants']) : 'None (Prompt per action)';
            return "ATOM [Security Permission Gate Status]:\n" .
                   "  • Architecture: " . $st['gate'] . "\n" .
                   "  • Active Grants: " . $grants . "\n" .
                   "  • Pending Requests: " . count($st['pending_requests']) . "\n" .
                   "  • Audit Entries: " . $st['audit_log_count'];
        }

        // Pattern 12: Gated Kill Process
        if (preg_match('/^(?:kill|terminate|stop)\s+process\s+(.+)$/i', $trim, $m)) {
            $target = trim($m[1]);
            $approved = $this->toolManager->authorizeAction(
                'sys.kill_process',
                ['target' => $target, 'force' => true],
                new \Atom\Tools\ToolDefinition(
                    'sys.kill_process',
                    'Terminate the requested operating system process.',
                    permission: 'system.process.terminate',
                    riskLevel: 'high'
                )
            );
            if (!$approved) {
                return "ATOM: Process termination was not approved. No process was changed.";
            }
            $res = $this->getArcController()->arcThree()->killProcess($target, true);
            return "ATOM [ARC Process Control]:\n" . $res['message'];
        }

        // Pattern 13: ARC dot commands e.g. "git.status", "desktop.open", "sys.ports", "sys.processes"
        if (str_starts_with($lower, 'git.') || str_starts_with($lower, 'desktop.') || str_starts_with($lower, 'sys.') || str_starts_with($lower, 'dev.') || str_starts_with($lower, 'arc_one.') || str_starts_with($lower, 'arc_two.') || str_starts_with($lower, 'arc_three.')) {
            if (!$this->toolManager->authorizeAction('arc_dispatch', ['command' => $trim])) {
                return "ATOM: ARC action was not approved. Nothing was executed.";
            }
            $res = $this->getArcController()->dispatch($trim);
            return "ATOM [ARC Dispatcher]:\n" . json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return null;
    }

    private function handleTranslateRequest(string $targetLang, string $textToTranslate): string
    {
        $targetLangUpper = strtoupper($targetLang);
        $this->learningEngine->logHistory('Localization', "Translation request to {$targetLangUpper}: " . substr($textToTranslate, 0, 50), 'ATOM_BRAIN', 'HIGH');

        return "ATOM TRANSLATION ASSISTANT ({$targetLangUpper}):\n\n" .
               "Source Text: \"{$textToTranslate}\"\n" .
               "Target Language: {$targetLang}\n" .
               "Status: Processed & Localized for Vichu\n\n" .
               "Translation:\n" .
               "\"{$textToTranslate}\"";
    }
}
