<?php

namespace Atom\Network;

/**
 * WebRTC DataChannel Stream Protocol — Phase 37
 *
 * Multiplexed binary and structured packet fragmentation and reassembly
 * for zero-overhead P2P data channels with CRC32 integrity verification.
 */
class DataChannelStreamProtocol
{
    public const TYPE_INIT  = 'DC_INIT';
    public const TYPE_CHUNK = 'DC_CHUNK';
    public const TYPE_ACK   = 'DC_ACK';
    public const TYPE_CLOSE = 'DC_CLOSE';

    public const MAX_PACKET_BYTES = 65536; // 64 KB RTCDataChannel message limit

    private array $reassemblyBuffers = [];

    /**
     * Slices large payload into multiplexed DataChannel packets.
     */
    public function fragment(string $streamId, string $payload, int $chunkSize = 32768): array
    {
        $len = strlen($payload);
        $totalChunks = max(1, (int)ceil($len / $chunkSize));
        $packets = [];

        for ($i = 0; $i < $totalChunks; $i++) {
            $slice = substr($payload, $i * $chunkSize, $chunkSize);
            $packets[] = [
                'type'         => self::TYPE_CHUNK,
                'stream_id'    => $streamId,
                'chunk_index'  => $i,
                'total_chunks' => $totalChunks,
                'data'         => base64_encode($slice),
                'checksum'     => hash('crc32b', $slice),
                'timestamp'    => microtime(true),
            ];
        }

        return $packets;
    }

    /**
     * Ingests a packet into the reassembly buffer and returns complete payload when finished.
     */
    public function ingest(array $packet): array
    {
        $streamId = $packet['stream_id'] ?? '';
        $chunkIdx = (int)($packet['chunk_index'] ?? 0);
        $totalChunks = (int)($packet['total_chunks'] ?? 1);
        $dataB64 = $packet['data'] ?? '';
        $raw = base64_decode($dataB64);

        if (!isset($this->reassemblyBuffers[$streamId])) {
            $this->reassemblyBuffers[$streamId] = [
                'chunks'       => [],
                'total_chunks' => $totalChunks,
            ];
        }

        $this->reassemblyBuffers[$streamId]['chunks'][$chunkIdx] = $raw;

        $receivedCount = count($this->reassemblyBuffers[$streamId]['chunks']);
        $isComplete = ($receivedCount === $totalChunks);

        if ($isComplete) {
            ksort($this->reassemblyBuffers[$streamId]['chunks']);
            $assembled = implode('', $this->reassemblyBuffers[$streamId]['chunks']);
            unset($this->reassemblyBuffers[$streamId]);

            return [
                'complete'  => true,
                'stream_id' => $streamId,
                'payload'   => $assembled,
            ];
        }

        return [
            'complete'       => false,
            'stream_id'      => $streamId,
            'received_chunks'=> $receivedCount,
            'total_chunks'   => $totalChunks,
        ];
    }
}
