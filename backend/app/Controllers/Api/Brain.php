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
}




