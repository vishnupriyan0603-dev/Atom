<?php

namespace App\Controllers\Api;

use Atom\Brain\IntentEngine;
use Atom\Brain\ContextEngine;
use Atom\Brain\AwarenessEngine;
use Atom\Brain\PersonalityEngine;
use Atom\Brain\Voice\VoiceEngine;
use Atom\Brain\Device\DeviceAbstraction;

/**
 * Brain API Controller — Phase 23
 *
 * Endpoints
 * ---------
 * GET  /api/v1/brain/status       — brain state, environment, context summary
 * GET  /api/v1/brain/context      — active context window details
 * POST /api/v1/brain/reset-context — clear the context engine state
 * GET  /api/v1/brain/intent?q=<text> — dry-run intent classification (diagnostic)
 */
class Brain extends BaseApiController
{
    /**
     * GET /api/v1/brain/status
     * Returns the current brain state: environment awareness, context summary,
     * personality style, voice mode, and device context.
     */
    public function status()
    {
        $device      = new DeviceAbstraction();
        $awareness   = new AwarenessEngine(ROOTPATH . '../', $device);
        $personality = new PersonalityEngine();
        $voice       = new VoiceEngine();

        $envData = $awareness->getEnvironmentData();

        return $this->respondSuccess([
            'brain_state'       => 'idle',
            'personality_style' => $personality->getStyle(),
            'voice_mode'        => $voice->isVoiceModeActive(),
            'device'            => $device->getDeviceType(),
            'device_context'    => $device->getDeviceContext(),
            'environment'       => $envData,
            'uptime'            => 'N/A — stateless API call',
        ], 'Brain status retrieved');
    }

    /**
     * GET /api/v1/brain/context
     * Returns the current active context window summary.
     * Note: In the API, the ContextEngine is stateless per-request.
     * The true session context lives in the CLI/backend process.
     */
    public function context()
    {
        $context = new ContextEngine();

        return $this->respondSuccess([
            'context_summary'     => $context->getSummary(),
            'context_block'       => $context->buildContextBlock(),
            'note'                => 'API context is stateless. Full context tracking runs inside the CLI/backend AtomBrain process.',
        ], 'Context retrieved');
    }

    /**
     * POST /api/v1/brain/reset-context
     * Clears the active context engine state for a fresh conversation thread.
     */
    public function resetContext()
    {
        // The reset is acknowledged — actual CLI process context is reset
        // via the /brain:reset CLI command inside the running process.
        return $this->respondSuccess([
            'reset'  => true,
            'status' => 'Context reset acknowledged. Use /brain:reset in the CLI to reset the running Brain process context.',
        ], 'Context reset acknowledged');
    }

    /**
     * GET /api/v1/brain/intent?q=<text>
     * Dry-run intent classification for diagnostic purposes.
     */
    public function classifyIntent()
    {
        $q = trim($this->request->getGet('q') ?? '');
        if (empty($q)) {
            return $this->respondError('Missing query parameter: q', 400);
        }

        $engine = new IntentEngine();
        $result = $engine->classify($q);

        return $this->respondSuccess([
            'input'        => $q,
            'intent_result' => $result->toArray(),
        ], 'Intent classified');
    }

    /**
     * GET /api/v1/brain/graph
     * Returns the complete Atom Brain Knowledge & Learning Graph with Level 0-6 hierarchy.
     */
    public function graph()
    {
        $engine = new \Atom\Brain\AtomPersonalAssistantEngine();
        return $this->respondSuccess($engine->getLearningGraph(), 'Atom Brain learning graph and topic levels');
    }

    /**
     * POST /api/v1/brain/teach
     * Teaches Atom a new concept or correction and updates topic score and level.
     */
    public function teach()
    {
        $json = $this->request->getJSON(true) ?? [];
        $topic = $json['topic'] ?? 'General Knowledge';
        $concept = $json['concept'] ?? ($json['message'] ?? '');

        if (empty($concept)) {
            return $this->respondError('Concept or correction message cannot be empty', 400);
        }

        $engine = new \Atom\Brain\AtomPersonalAssistantEngine();
        $res = $engine->teachConcept($topic, $concept);

        return $this->respondSuccess($res, 'Concept taught to Atom successfully');
    }

    /**
     * POST /api/v1/brain/chat
     * Processes turn through Atom Personal Assistant Engine.
     */
    public function chat()
    {
        $json = $this->request->getJSON(true) ?? [];
        $message = trim($json['message'] ?? '');
        $mode = $json['mode'] ?? 'assistant';

        if (empty($message)) {
            return $this->respondError('Message cannot be empty', 400);
        }

        $engine = new \Atom\Brain\AtomPersonalAssistantEngine();
        $res = $engine->generateLocalResponse($message, $mode);

        // Also record in multi-turn memory
        $memoryEngine = new \Atom\Brain\MultiTurnContextMemoryEngine();
        $memoryEngine->recordTurn($message, $res['response'] ?? '');

        return $this->respondSuccess($res, 'Atom Personal Assistant response');
    }

    /**
     * GET /api/v1/brain/memory
     * Returns working memory, episodic facts, and sentiment velocity.
     */
    public function memory()
    {
        $memoryEngine = new \Atom\Brain\MultiTurnContextMemoryEngine();
        return $this->respondSuccess($memoryEngine->getMemoryStatus(), 'Atom Brain multi-turn memory status');
    }

    /**
     * POST /api/v1/brain/memory/remember
     * Explicitly store a user preference or fact into episodic memory.
     */
    public function remember()
    {
        $json = $this->request->getJSON(true) ?? [];
        $category = $json['category'] ?? 'preference';
        $fact = trim($json['fact'] ?? ($json['message'] ?? ''));
        $confidence = (float) ($json['confidence'] ?? 1.0);

        if (empty($fact)) {
            return $this->respondError('Fact or preference cannot be empty', 400);
        }

        $memoryEngine = new \Atom\Brain\MultiTurnContextMemoryEngine();
        $res = $memoryEngine->storeFact($category, $fact, $confidence);

        return $this->respondSuccess($res, 'Fact stored into episodic memory');
    }

    /**
     * POST /api/v1/brain/memory/forget
     * Forget or delete a specific fact or clear memory.
     */
    public function forget()
    {
        $json = $this->request->getJSON(true) ?? [];
        $identifier = trim($json['id'] ?? ($json['fact'] ?? ''));
        $clearAll = (bool) ($json['clear_all'] ?? false);
        $workingOnly = (bool) ($json['working_only'] ?? false);

        $memoryEngine = new \Atom\Brain\MultiTurnContextMemoryEngine();

        if ($clearAll) {
            $memoryEngine->clearMemory($workingOnly);
            return $this->respondSuccess(['cleared' => true, 'working_only' => $workingOnly], 'Memory cleared');
        }

        if (empty($identifier)) {
            return $this->respondError('Identifier or fact query required to forget', 400);
        }

        $forgotten = $memoryEngine->forgetFact($identifier);
        return $this->respondSuccess(['forgotten' => $forgotten, 'identifier' => $identifier], $forgotten ? 'Fact forgotten successfully' : 'Fact not found');
    }

    /**
     * POST /api/v1/brain/reason
     * Performs situational reasoning, financial/EMI calculation, or trade-off evaluation.
     */
    public function reason()
    {
        $json = $this->request->getJSON(true) ?? [];
        $query = trim($json['query'] ?? ($json['message'] ?? ''));
        $context = $json['context'] ?? [];

        if (empty($query)) {
            return $this->respondError('Query or situation prompt cannot be empty', 400);
        }

        $reasoner = new \Atom\Brain\AtomSituationReasonerEngine();
        $res = $reasoner->reason($query, $context);

        return $this->respondSuccess($res, 'Situation reasoning completed');
    }

    /**
     * GET /api/v1/brain/tools
     * Lists available minimalist brain tools and safety tiers.
     */
    public function tools()
    {
        $reasoner = new \Atom\Brain\AtomSituationReasonerEngine();
        return $this->respondSuccess([
            'tools' => $reasoner->getAvailableTools(),
            'policy' => 'Minimalist: Invoked only when verifiable computation or real-time inspection is necessary.'
        ], 'Available minimalist tools');
    }

    /**
     * POST /api/v1/brain/tool/execute
     * Safely executes a whitelisted minimalist tool.
     */
    public function executeTool()
    {
        $json = $this->request->getJSON(true) ?? [];
        $toolName = trim($json['tool'] ?? ($json['name'] ?? ''));
        $params = $json['parameters'] ?? ($json['params'] ?? []);

        if (empty($toolName)) {
            return $this->respondError('Tool name is required', 400);
        }

        $reasoner = new \Atom\Brain\AtomSituationReasonerEngine();
        $res = $reasoner->executeTool($toolName, $params);

        if (!empty($res['success'])) {
            return $this->respondSuccess($res, "Tool {$toolName} executed successfully");
        }

        return $this->respondError($res['error'] ?? 'Tool execution failed', 400, $res);
    }

    /**
     * POST /api/v1/brain/voice/synthesize
     * Synthesizes text with emotional prosody parameters and W3C SSML.
     */
    public function synthesizeVoice()
    {
        $json = $this->request->getJSON(true) ?? [];
        $text = trim($json['text'] ?? ($json['message'] ?? ''));
        $profile = $json['profile'] ?? 'heroic_ben10';
        $emotion = $json['emotion'] ?? 'neutral';

        if (empty($text)) {
            return $this->respondError('Text cannot be empty for voice synthesis', 400);
        }

        $voiceEngine = new \Atom\Brain\AtomVoiceProsodyEngine();
        $res = $voiceEngine->synthesize($text, $profile, $emotion);

        return $this->respondSuccess($res, 'Voice prosody synthesized successfully');
    }

    /**
     * GET /api/v1/brain/voice/profiles
     * Returns all calibrated voice profiles.
     */
    public function voiceProfiles()
    {
        $voiceEngine = new \Atom\Brain\AtomVoiceProsodyEngine();
        return $this->respondSuccess([
            'profiles' => $voiceEngine->getVoiceProfiles(),
            'default' => 'heroic_ben10'
        ], 'Atom Voice Profiles');
    }

    /**
     * POST /api/v1/brain/voice/stream
     * Coordinates duplex voice streaming and handles speech interruption events.
     */
    public function voiceStream()
    {
        $json = $this->request->getJSON(true) ?? [];
        $streamId = $json['stream_id'] ?? uniqid('vstream_', true);
        $event = $json['event'] ?? 'start_speech';
        $payload = $json['payload'] ?? [];

        $voiceEngine = new \Atom\Brain\AtomVoiceProsodyEngine();
        $res = $voiceEngine->handleStreamTurn($streamId, $event, $payload);

        if (!empty($res['success'])) {
            return $this->respondSuccess($res, 'Voice stream event processed');
        }

        return $this->respondError($res['error'] ?? 'Stream event failed', 400);
    }

    /**
     * POST /api/v1/brain/planner/create
     * Decomposes a user goal into an autonomous multi-step DAG plan.
     */
    public function createPlan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $goal = trim($json['goal'] ?? ($json['text'] ?? ''));
        $templateKey = $json['template'] ?? null;

        if (empty($goal) && empty($templateKey)) {
            return $this->respondError('Goal description or template is required', 400);
        }

        $planner = new \Atom\Brain\AtomGoalPlannerEngine();
        $res = $planner->createPlan($goal ?: 'Execute Plan Preset', $templateKey);

        if (!empty($res['success'])) {
            return $this->respondSuccess($res, 'Goal decomposed into actionable multi-step plan');
        }

        return $this->respondError($res['error'] ?? 'Failed to create plan', 400);
    }

    /**
     * POST /api/v1/brain/planner/step
     * Advances a plan step with automated self-correction upon error.
     */
    public function stepPlan()
    {
        $json = $this->request->getJSON(true) ?? [];
        $plan = $json['plan'] ?? [];
        $taskId = $json['task_id'] ?? '';
        $simulateSuccess = $json['success'] ?? true;
        $simulatedError = $json['error'] ?? null;

        if (empty($plan) || empty($taskId)) {
            return $this->respondError('Plan payload and task_id are required', 400);
        }

        $planner = new \Atom\Brain\AtomGoalPlannerEngine();
        $res = $planner->advanceStep($plan, $taskId, (bool) $simulateSuccess, $simulatedError);

        if (!empty($res['success'])) {
            return $this->respondSuccess($res, "Step {$taskId} processed");
        }

        return $this->respondError($res['error'] ?? 'Failed to advance step', 400, $res);
    }

    /**
     * GET /api/v1/brain/planner/templates
     * Returns preset multi-step goal plan templates.
     */
    public function planTemplates()
    {
        $planner = new \Atom\Brain\AtomGoalPlannerEngine();
        return $this->respondSuccess([
            'templates' => $planner->getTemplates()
        ], 'Goal Plan Templates');
    }

    /**
     * POST /api/v1/brain/planner/google-search
     * Performs Google Search & live internet information harvesting for multi-step plans.
     */
    public function googleSearchPlanner()
    {
        $json = $this->request->getJSON(true) ?? [];
        $query = trim($json['query'] ?? ($json['q'] ?? ($json['goal'] ?? '')));
        $googleConfig = $json['credentials'] ?? ($json['config'] ?? []);

        if (empty($query)) {
            return $this->respondError('Query is required for Google Search Harvester', 400);
        }

        $planner = new \Atom\Brain\AtomGoalPlannerEngine();
        $res = $planner->executeGoogleSearchHarvest($query, $googleConfig);

        return $this->respondSuccess($res, 'Internet information harvested successfully');
    }

    /**
     * GET /api/v1/brain/meta/telemetry
     * Returns aggregated master telemetry across all 6 Atom Brain phases.
     */
    public function metaTelemetry()
    {
        $metaEngine = new \Atom\Brain\AtomMetaCognitionEngine();
        $res = $metaEngine->getMasterTelemetry();
        return $this->respondSuccess($res, 'Atom Brain Master Telemetry');
    }

    /**
     * POST /api/v1/brain/meta/evaluate
     * Evaluates a turn across 5 meta-cognitive quality dimensions.
     */
    public function evaluateMetaCognition()
    {
        $json = $this->request->getJSON(true) ?? [];
        $userInput = trim($json['input'] ?? ($json['query'] ?? ''));
        $response = trim($json['response'] ?? ($json['assistant'] ?? ''));
        $context = $json['context'] ?? [];

        if (empty($userInput) || empty($response)) {
            return $this->respondError('Both user input and assistant response are required', 400);
        }

        $metaEngine = new \Atom\Brain\AtomMetaCognitionEngine();
        $res = $metaEngine->evaluateTurn($userInput, $response, $context);

        if (!empty($res['success'])) {
            return $this->respondSuccess($res, 'Meta-cognitive quality evaluation complete');
        }

        return $this->respondError($res['error'] ?? 'Evaluation failed', 400);
    }

    /**
     * POST /api/v1/brain/meta/evolve
     * Triggers autonomous prompt calibration and synapse weight adjustments.
     */
    public function evolveMetaCognition()
    {
        $json = $this->request->getJSON(true) ?? [];
        $evaluations = $json['evaluations'] ?? [];

        $metaEngine = new \Atom\Brain\AtomMetaCognitionEngine();
        $res = $metaEngine->evolveSynapseWeights($evaluations);

        return $this->respondSuccess($res, 'Synapse weights evolved successfully');
    }

    /**
     * GET /api/v1/brain/relationship
     * Returns current user relationship profile, active subject, and context prompt.
     */
    public function relationship()
    {
        $relEngine = new \Atom\Brain\AtomRelationshipEngine();
        return $this->respondSuccess([
            'profile' => $relEngine->getUserProfile(),
            'context_prompt' => $relEngine->buildRelationshipContextPrompt(),
            'user_name' => $relEngine->getUserName(),
            'active_topic' => $relEngine->getActiveTopic(),
            'active_subject' => $relEngine->getActiveSubject(),
        ], 'Atom Relationship & Context Profile');
    }

    /**
     * POST /api/v1/brain/relationship/process
     * Evaluates a turn through the relationship engine (identity, topic continuity, follow-ups).
     */
    public function processRelationship()
    {
        $json = $this->request->getJSON(true) ?? [];
        $message = trim($json['message'] ?? ($json['query'] ?? ($json['input'] ?? '')));

        if (empty($message)) {
            return $this->respondError('Message is required', 400);
        }

        $relEngine = new \Atom\Brain\AtomRelationshipEngine();
        $res = $relEngine->processMessage($message);

        return $this->respondSuccess($res, 'Relationship message processed');
    }

    /**
     * POST /api/v1/brain/relationship/set
     * Explicitly sets user name or active topic/subject.
     */
    public function setRelationship()
    {
        $json = $this->request->getJSON(true) ?? [];
        $name = trim($json['name'] ?? '');
        $topic = trim($json['topic'] ?? '');
        $subject = trim($json['subject'] ?? '');

        $relEngine = new \Atom\Brain\AtomRelationshipEngine();
        if (!empty($name)) {
            $relEngine->setUserName($name);
        }
        if (!empty($topic) || !empty($subject)) {
            $relEngine->setActiveTopic($topic ?: $subject, $subject ?: $topic);
        }

        return $this->respondSuccess([
            'profile' => $relEngine->getUserProfile(),
        ], 'Relationship context updated');
    }
}






