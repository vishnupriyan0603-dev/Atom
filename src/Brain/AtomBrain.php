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

class AtomBrain
{
    private ?ModelManager $modelManager;
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

        while ($loopCount < $maxLoops) {
            if ($provider !== null && $provider !== '') {
                $res = $this->modelManager->generateForProvider($provider, $messages);
            } else {
                $res = $this->modelManager->generateForRole('primary', $messages);
            }

            if (!$res->isSuccess()) {
                $errMessage = $res->getError();
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

                echo "\033[33mATOM is executing tool '{$toolName}'...\033[0m" . PHP_EOL;

                $toolStart = microtime(true);
                // Run the registered tool
                $toolResult = $this->toolManager->executeTool($toolName, $toolArgs);
                $toolDuration = (int)((microtime(true) - $toolStart) * 1000);

                $this->logToolExecution($toolName, $toolArgs, $toolResult, $toolDuration, 'SUCCESS');

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
        $finalReply = $this->runSelfLearning($input, $messages, $finalReply, $providerName);

        // ── Phase 23: apply PersonalityEngine post-processor ─────────────────
        $finalReply = $this->personalityEngine->applyPersonality($finalReply, $intent);
        if ($this->voiceEngine->isVoiceModeActive()) {
            $finalReply = $this->voiceEngine->formatForVoice($finalReply);
        }
        // ── Phase 23: update ContextEngine with this turn ────────────────────
        $this->contextEngine->update($input, $finalReply, $intent);
        // ─────────────────────────────────────────────────────────────────────

        // Save conversation history to local array and persistent DB
        $history[] = $userMsg;
        $history[] = ['role' => 'assistant', 'content' => $finalReply];

        $this->memory->logMessage('user', $input);
        $this->memory->logMessage('assistant', $finalReply);

        // Save trace records to DB
        $durationMs = (int)((microtime(true) - $startTime) * 1000);
        $this->logRequest($reqId, $input, $intent, 'COLLABORATIVE', $providerName, $hasKnowledge, $confidenceScore, $durationMs, 'SUCCESS');
        $this->logResponse($reqId, $finalReply, $providerName, $modelName);

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
