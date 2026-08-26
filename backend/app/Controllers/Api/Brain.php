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

        return $this->respondSuccess($res, 'Atom Personal Assistant response');
    }
}

