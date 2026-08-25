<?php

namespace App\Controllers\Api;

use Atom\Network\WebRtcFileTransferEngine;

/**
 * WebRtcTransfer API Controller — Phase 66
 */
class WebRtcTransfer extends BaseApiController
{
    private static ?WebRtcFileTransferEngine $engine = null;

    private function getEngine(): WebRtcFileTransferEngine
    {
        if (self::$engine === null) {
            self::$engine = new WebRtcFileTransferEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/network/webrtc/transfer/prepare
     */
    public function prepare()
    {
        $json = $this->request->getJSON(true) ?? [];
        $fileName = $json['file_name'] ?? 'platform_snapshot.bin';
        $content = $json['content'] ?? str_repeat("ATOM_WEBRTC_DATA_CHUNK_PAYLOAD_TEST", 100);
        $chunkSize = (int) ($json['chunk_size_bytes'] ?? 512);

        $engine = $this->getEngine();
        $prepared = $engine->prepareTransfer($fileName, $content, $chunkSize);

        return $this->respondSuccess($prepared, 'WebRTC file transfer prepared and chunked');
    }

    /**
     * POST /api/network/webrtc/transfer/ingest-chunk
     */
    public function ingestChunk()
    {
        $json = $this->request->getJSON(true) ?? [];
        $transferId = $json['transfer_id'] ?? '';
        $idx = (int) ($json['chunk_index'] ?? 0);
        $data = $json['data'] ?? '';
        $checksum = $json['chunk_checksum'] ?? '';

        $engine = $this->getEngine();
        $res = $engine->ingestChunk($transferId, $idx, $data, $checksum);

        return $this->respondSuccess($res, 'Chunk ingested');
    }

    /**
     * POST /api/network/webrtc/transfer/reassemble
     */
    public function reassemble()
    {
        $json = $this->request->getJSON(true) ?? [];
        $transferId = $json['transfer_id'] ?? '';

        $engine = $this->getEngine();
        $res = $engine->reassembleFile($transferId);

        return $this->respondSuccess($res, 'File reassembled and verified');
    }
}
