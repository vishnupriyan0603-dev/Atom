<?php

namespace App\Controllers\Api;

use Atom\Voice\AudioDuplexProtocol;
use Atom\Voice\WakeWordDetector;
use Atom\Voice\ConversationalTurnTakingManager;
use Atom\Voice\AudioEmotionAnalyzer;

/**
 * Real-Time Voice Duplex & Streaming Audio API Controller — Phase 34
 *
 * Endpoints:
 * - POST /api/v1/voice/duplex/start     — Initialize a real-time duplex streaming session
 * - POST /api/v1/voice/duplex/chunk     — Push audio frame chunk with VAD & wake detection
 * - POST /api/v1/voice/duplex/interrupt — Trigger barge-in interrupt signal
 * - POST /api/v1/voice/duplex/emotion   — Analyze prosodic audio features and classify emotion
 * - GET  /api/v1/voice/duplex/state     — Get conversational turn-taking state
 */
class VoiceDuplex extends BaseApiController
{
    private static ?AudioDuplexProtocol $protocolInstance = null;
    private static ?WakeWordDetector $wakeInstance = null;
    private static ?ConversationalTurnTakingManager $turnInstance = null;
    private static ?AudioEmotionAnalyzer $emotionInstance = null;

    private function getProtocol(): AudioDuplexProtocol
    {
        if (self::$protocolInstance === null) {
            self::$protocolInstance = new AudioDuplexProtocol();
        }
        return self::$protocolInstance;
    }

    private function getWakeDetector(): WakeWordDetector
    {
        if (self::$wakeInstance === null) {
            self::$wakeInstance = new WakeWordDetector();
        }
        return self::$wakeInstance;
    }

    private function getTurnManager(): ConversationalTurnTakingManager
    {
        if (self::$turnInstance === null) {
            self::$turnInstance = new ConversationalTurnTakingManager();
        }
        return self::$turnInstance;
    }

    private function getEmotionAnalyzer(): AudioEmotionAnalyzer
    {
        if (self::$emotionInstance === null) {
            self::$emotionInstance = new AudioEmotionAnalyzer();
        }
        return self::$emotionInstance;
    }

    /**
     * POST /api/v1/voice/duplex/start
     */
    public function start()
    {
        $json = $this->request->getJSON(true) ?? [];
        $sessionId = 'duplex_' . bin2hex(random_bytes(8));
        $turn = $this->getTurnManager();
        $turn->reset();

        return $this->respondSuccess([
            'session_id'    => $sessionId,
            'state'         => $turn->getState(),
            'protocol'      => 'PCM_16K_16BIT_MONO',
            'silence_limit' => ConversationalTurnTakingManager::DEFAULT_SILENCE_TIMEOUT_MS,
        ], 'Voice duplex stream session initialized');
    }

    /**
     * POST /api/v1/voice/duplex/chunk
     */
    public function chunk()
    {
        $json = $this->request->getJSON(true) ?? [];
        $protocol = $this->getProtocol();
        $wake = $this->getWakeDetector();
        $turn = $this->getTurnManager();

        try {
            $frame = $protocol->parseFrame($json);
            $text = $json['text'] ?? '';
            $vad = (bool)($json['vad_active'] ?? (!empty($frame['payload']) || !empty($text)));

            if ($vad) {
                $turn->onUserSpeechDetected();
            }

            $wakeResult = !empty($text) ? $wake->detect($text) : ['detected' => false];

            return $this->respondSuccess([
                'sequence'      => $frame['sequence'],
                'current_state' => $turn->getState(),
                'wake_detected' => $wakeResult['detected'],
                'wake_phrase'   => $wakeResult['phrase'] ?? null,
                'turn_count'    => $turn->getTurnCount(),
            ], 'Audio chunk processed');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/voice/duplex/interrupt
     */
    public function interrupt()
    {
        $turn = $this->getTurnManager();
        $res = $turn->interrupt();

        return $this->respondSuccess($res, 'Barge-in interruption signal dispatched');
    }

    /**
     * POST /api/v1/voice/duplex/emotion
     */
    public function emotion()
    {
        $json = $this->request->getJSON(true) ?? [];
        $analyzer = $this->getEmotionAnalyzer();
        $features = $json['features'] ?? $json;

        $result = $analyzer->analyze($features);
        return $this->respondSuccess($result, 'Audio emotion classified');
    }

    /**
     * GET /api/v1/voice/duplex/state
     */
    public function state()
    {
        $turn = $this->getTurnManager();
        return $this->respondSuccess([
            'state'        => $turn->getState(),
            'turn_count'   => $turn->getTurnCount(),
            'recent_turns' => $turn->getEventHistory(10),
            'wake_phrases' => $this->getWakeDetector()->getWakePhrases(),
        ], 'Conversational turn state retrieved');
    }
}
