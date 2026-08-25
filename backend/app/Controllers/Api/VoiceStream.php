<?php

namespace App\Controllers\Api;

use Atom\Voice\AudioDuplexStreamSession;
use Atom\Voice\RealtimeFormantShifterEngine;

/**
 * VoiceStream API Controller — Phase 46
 */
class VoiceStream extends BaseApiController
{
    private static ?AudioDuplexStreamSession $activeSession = null;

    private function getActiveSession(): AudioDuplexStreamSession
    {
        if (self::$activeSession === null) {
            self::$activeSession = new AudioDuplexStreamSession();
        }
        return self::$activeSession;
    }

    /**
     * POST /api/voice/stream/session/start
     */
    public function startSession()
    {
        $json = $this->request->getJSON(true) ?? [];
        $pitchScale = (float)($json['pitch_scale'] ?? 1.18);
        $formantScale = (float)($json['formant_scale'] ?? 1.12);
        $targetF0 = (float)($json['target_f0'] ?? 245.0);

        $shifter = new RealtimeFormantShifterEngine($pitchScale, $formantScale, $targetF0);
        self::$activeSession = new AudioDuplexStreamSession(null, $shifter);

        return $this->respondSuccess([
            'session_id' => self::$activeSession->getSessionId(),
            'status' => 'STREAM_ACTIVE',
            'sample_rate' => 16000,
            'formant_parameters' => $shifter->getParameters(),
        ], 'Full-duplex audio stream session initialized');
    }

    /**
     * POST /api/voice/stream/chunk
     */
    public function processChunk()
    {
        $json = $this->request->getJSON(true) ?? [];
        $samples = $json['samples'] ?? [];
        $rawBase64 = $json['pcm_base64'] ?? '';

        $session = $this->getActiveSession();

        if (!empty($rawBase64)) {
            $binary = base64_decode($rawBase64);
            $result = $session->processIngressFrame($binary);
        } else {
            if (empty($samples)) {
                // Synthesize simulated live mic frame
                $samples = [];
                for ($i = 0; $i < 256; $i++) {
                    $samples[] = sin($i * 0.15) * 0.4 + (mt_rand(-50, 50) / 1000.0);
                }
            }
            $result = $session->processIngressFrame($samples);
        }

        return $this->respondSuccess($result, 'Audio chunk processed through live formant shifter');
    }

    /**
     * POST /api/voice/stream/formants/set
     */
    public function setFormants()
    {
        $json = $this->request->getJSON(true) ?? [];
        $session = $this->getActiveSession();
        $session->getShifter()->tuneParameters($json);

        return $this->respondSuccess([
            'updated_parameters' => $session->getShifter()->getParameters(),
        ], 'Live formant frequency and pitch parameters tuned');
    }

    /**
     * GET /api/voice/stream/session/stats
     */
    public function sessionStats()
    {
        $session = $this->getActiveSession();
        return $this->respondSuccess($session->getSessionTelemetry(), 'Live voice stream telemetry');
    }

    /**
     * DELETE /api/voice/stream/session/stop
     */
    public function stopSession()
    {
        if (self::$activeSession !== null) {
            $id = self::$activeSession->getSessionId();
            self::$activeSession = null;
            return $this->respondSuccess(['session_id' => $id, 'status' => 'TERMINATED'], 'Voice stream session stopped');
        }
        return $this->respondSuccess(['status' => 'INACTIVE'], 'No active stream session');
    }
}
