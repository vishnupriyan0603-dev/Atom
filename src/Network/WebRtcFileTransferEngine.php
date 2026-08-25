<?php

namespace Atom\Network;

use Atom\Security\SecretRedactor;

/**
 * WebRtcFileTransferEngine — Phase 66
 * Real-time WebRTC peer data channel chunked file transfer mesh and SHA-256 integrity validator.
 */
class WebRtcFileTransferEngine
{
    private SecretRedactor $redactor;
    private array $activeTransfers = [];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Prepare a file payload for chunked WebRTC data channel transfer.
     *
     * @param string $fileName
     * @param string $fileContent Base64 or raw string
     * @param int $chunkSizeBytes Default 64KB (65536 bytes)
     * @return array [ 'transfer_id' => string, 'total_chunks' => int, 'checksum' => string, 'chunks' => array ]
     */
    public function prepareTransfer(string $fileName, string $fileContent, int $chunkSizeBytes = 65536): array
    {
        if (empty($fileName) || empty($fileContent)) {
            return [
                'success' => false,
                'error' => 'File name and content cannot be empty',
                'transfer_id' => '',
                'total_chunks' => 0,
            ];
        }

        $cleanName = basename($this->redactor->redact($fileName));
        $transferId = bin2hex(random_bytes(12));
        $fileSize = strlen($fileContent);
        $checksum = hash('sha256', $fileContent);

        $chunkSize = max(16, min(262144, $chunkSizeBytes));
        $rawChunks = str_split($fileContent, $chunkSize);
        $totalChunks = count($rawChunks);

        $chunks = [];
        foreach ($rawChunks as $idx => $chunkData) {
            $chunks[] = [
                'transfer_id' => $transferId,
                'chunk_index' => $idx,
                'total_chunks' => $totalChunks,
                'size_bytes' => strlen($chunkData),
                'chunk_checksum' => hash('sha256', $chunkData),
                'data' => base64_encode($chunkData),
            ];
        }

        $this->activeTransfers[$transferId] = [
            'transfer_id' => $transferId,
            'file_name' => $cleanName,
            'file_size_bytes' => $fileSize,
            'total_chunks' => $totalChunks,
            'expected_checksum' => $checksum,
            'received_chunks' => [],
            'start_time' => microtime(true),
        ];

        return [
            'success' => true,
            'transfer_id' => $transferId,
            'file_name' => $cleanName,
            'file_size_bytes' => $fileSize,
            'total_chunks' => $totalChunks,
            'checksum_sha256' => $checksum,
            'chunks' => $chunks,
        ];
    }

    /**
     * Ingest a chunk into the transfer buffer.
     */
    public function ingestChunk(string $transferId, int $chunkIndex, string $base64Data, string $chunkChecksum): array
    {
        if (!isset($this->activeTransfers[$transferId])) {
            return ['success' => false, 'error' => 'Transfer session not found'];
        }

        $rawData = base64_decode($base64Data);
        $computedHash = hash('sha256', $rawData);

        if (!hash_equals($chunkChecksum, $computedHash)) {
            return ['success' => false, 'error' => 'Chunk checksum mismatch (corrupted chunk)'];
        }

        $this->activeTransfers[$transferId]['received_chunks'][$chunkIndex] = $rawData;
        $receivedCount = count($this->activeTransfers[$transferId]['received_chunks']);
        $totalCount = $this->activeTransfers[$transferId]['total_chunks'];

        return [
            'success' => true,
            'transfer_id' => $transferId,
            'chunk_index' => $chunkIndex,
            'received_chunks' => $receivedCount,
            'total_chunks' => $totalCount,
            'is_complete' => $receivedCount === $totalCount,
            'progress_pct' => round(($receivedCount / max(1, $totalCount)) * 100, 1),
        ];
    }

    /**
     * Reassemble all received chunks and verify overall SHA-256 checksum.
     */
    public function reassembleFile(string $transferId): array
    {
        if (!isset($this->activeTransfers[$transferId])) {
            return ['success' => false, 'error' => 'Transfer session not found'];
        }

        $session = $this->activeTransfers[$transferId];
        $total = $session['total_chunks'];
        $received = $session['received_chunks'];

        if (count($received) < $total) {
            return [
                'success' => false,
                'error' => "Cannot reassemble: only received " . count($received) . " of {$total} chunks",
            ];
        }

        // Sort by chunk index and concatenate
        ksort($received);
        $assembledContent = implode('', $received);
        $computedChecksum = hash('sha256', $assembledContent);

        $isValid = hash_equals($session['expected_checksum'], $computedChecksum);
        $duration = max(0.001, microtime(true) - $session['start_time']);
        $bitrateMbps = round(($session['file_size_bytes'] * 8 / 1000000) / $duration, 2);

        return [
            'success' => $isValid,
            'transfer_id' => $transferId,
            'file_name' => $session['file_name'],
            'file_size_bytes' => strlen($assembledContent),
            'checksum_verified' => $isValid,
            'checksum_sha256' => $computedChecksum,
            'duration_sec' => round($duration, 3),
            'bitrate_mbps' => $bitrateMbps,
            'status' => $isValid ? 'TRANSFER_VERIFIED_SUCCESS' : 'CHECKSUM_FAILED',
        ];
    }
}
