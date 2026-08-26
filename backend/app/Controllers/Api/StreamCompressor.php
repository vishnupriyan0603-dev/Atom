<?php

namespace App\Controllers\Api;

use Atom\Network\StreamFrameCompressorEngine;

/**
 * StreamCompressor API Controller — Phase 90 Landmark
 */
class StreamCompressor extends BaseApiController
{
    private static ?StreamFrameCompressorEngine $engine = null;

    private function getEngine(): StreamFrameCompressorEngine
    {
        if (self::$engine === null) {
            self::$engine = new StreamFrameCompressorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/network/stream/compress
     */
    public function compress()
    {
        $json = $this->request->getJSON(true) ?? [];
        $payload = $json['payload'] ?? "Sample payload data repeated for compression testing. Sample payload data repeated for compression testing.";
        $codec = $json['codec'] ?? 'deflate';
        $level = (int) ($json['level'] ?? 6);

        $engine = $this->getEngine();
        $res = $engine->encodeFrame($payload, $codec, $level);

        return $this->respondSuccess($res, 'Payload encoded into compressed binary wire frame');
    }

    /**
     * POST /api/network/stream/decompress
     */
    public function decompress()
    {
        $json = $this->request->getJSON(true) ?? [];
        $hex = $json['frame_hex'] ?? '';

        if ($hex === '') {
            return $this->respondError('frame_hex parameter is required', 400);
        }

        $binary = @hex2bin($hex);
        if ($binary === false) {
            return $this->respondError('Invalid hex string provided', 400);
        }

        $engine = $this->getEngine();
        $res = $engine->decodeFrame($binary);

        return $this->respondSuccess($res, 'Binary frame decoded and verified');
    }

    /**
     * GET /api/network/stream/codecs
     */
    public function codecs()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getSupportedCodecs(), 'Supported stream codecs');
    }
}
