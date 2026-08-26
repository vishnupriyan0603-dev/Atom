<?php

namespace Atom\Network;

use Atom\Security\SecretRedactor;

/**
 * StreamFrameCompressorEngine — Phase 90 Landmark
 * Zero-copy stream compressor, binary framing wire protocol, CRC32 integrity verification, and multi-codec engine.
 */
class StreamFrameCompressorEngine
{
    private SecretRedactor $redactor;
    public const MAGIC_HEADER = "\xAA\x55"; // 2-byte sync word
    public const PROTOCOL_VERSION = 1;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Compress payload and package into binary frame protocol.
     *
     * Frame format:
     * [2 bytes Magic Sync] [1 byte Version] [1 byte Codec] [4 bytes CRC32] [4 bytes OriginalLen] [4 bytes CompressedLen] [N bytes Data]
     *
     * @param string $payload Raw payload string
     * @param string $codec 'deflate', 'gzip', 'raw'
     * @param int $level Compression level 1-9
     * @return array Encoded binary frame info
     */
    public function encodeFrame(string $payload, string $codec = 'deflate', int $level = 6): array
    {
        if ($payload === '') {
            return [
                'success' => false,
                'error' => 'Payload cannot be empty',
                'frame_hex' => '',
            ];
        }

        $cleanPayload = $this->redactor->redact($payload);
        $originalLen = strlen($cleanPayload);
        $crc = (int) sprintf('%u', crc32($cleanPayload));

        $compressedData = $cleanPayload;
        $codecByte = 0; // 0=raw, 1=deflate, 2=gzip

        switch (strtolower($codec)) {
            case 'deflate':
                $compressedData = gzdeflate($cleanPayload, max(1, min(9, $level)));
                $codecByte = 1;
                break;
            case 'gzip':
                $compressedData = gzencode($cleanPayload, max(1, min(9, $level)));
                $codecByte = 2;
                break;
            case 'raw':
            default:
                $compressedData = $cleanPayload;
                $codecByte = 0;
                break;
        }

        $compressedLen = strlen($compressedData);
        $ratio = round($originalLen / max(1, $compressedLen), 2);
        $spaceSavedPct = round((1.0 - ($compressedLen / max(1, $originalLen))) * 100, 1);

        // Binary pack header (16 bytes header)
        $header = self::MAGIC_HEADER
            . chr(self::PROTOCOL_VERSION)
            . chr($codecByte)
            . pack('N', $crc)
            . pack('N', $originalLen)
            . pack('N', $compressedLen);

        $binaryFrame = $header . $compressedData;

        return [
            'success' => true,
            'codec' => $codec,
            'original_bytes' => $originalLen,
            'compressed_bytes' => $compressedLen,
            'total_frame_bytes' => strlen($binaryFrame),
            'compression_ratio' => $ratio,
            'space_saved_pct' => $spaceSavedPct,
            'crc32_checksum' => dechex($crc),
            'frame_hex' => bin2hex($binaryFrame),
            'binary_frame' => $binaryFrame,
        ];
    }

    /**
     * Decode and decompress binary frame wire protocol.
     */
    public function decodeFrame(string $binaryFrame): array
    {
        if (strlen($binaryFrame) < 16) {
            return [
                'success' => false,
                'error' => 'INVALID_FRAME_LENGTH',
                'payload' => '',
            ];
        }

        $magic = substr($binaryFrame, 0, 2);
        if ($magic !== self::MAGIC_HEADER) {
            return [
                'success' => false,
                'error' => 'INVALID_MAGIC_SYNC_BYTES',
                'payload' => '',
            ];
        }

        $version = ord($binaryFrame[2]);
        $codecByte = ord($binaryFrame[3]);
        $crc = unpack('N', substr($binaryFrame, 4, 4))[1];
        $originalLen = unpack('N', substr($binaryFrame, 8, 4))[1];
        $compressedLen = unpack('N', substr($binaryFrame, 12, 4))[1];

        $data = substr($binaryFrame, 16);
        if (strlen($data) !== $compressedLen) {
            return [
                'success' => false,
                'error' => 'PAYLOAD_LENGTH_MISMATCH',
                'payload' => '',
            ];
        }

        $decompressed = '';
        switch ($codecByte) {
            case 1: // Deflate
                $decompressed = @gzinflate($data);
                break;
            case 2: // Gzip
                $decompressed = @gzdecode($data);
                break;
            case 0: // Raw
            default:
                $decompressed = $data;
                break;
        }

        if ($decompressed === false) {
            return [
                'success' => false,
                'error' => 'DECOMPRESSION_FAILED',
                'payload' => '',
            ];
        }

        // Verify CRC32 checksum integrity
        $actualCrc = (int) sprintf('%u', crc32($decompressed));
        if ($actualCrc !== $crc) {
            return [
                'success' => false,
                'error' => 'CRC32_INTEGRITY_CHECK_FAILED',
                'payload' => '',
            ];
        }

        return [
            'success' => true,
            'protocol_version' => $version,
            'codec' => $codecByte === 1 ? 'deflate' : ($codecByte === 2 ? 'gzip' : 'raw'),
            'original_bytes' => $originalLen,
            'decompressed_bytes' => strlen($decompressed),
            'crc32_checksum' => dechex($crc),
            'payload' => $decompressed,
        ];
    }

    public function getSupportedCodecs(): array
    {
        return [
            'deflate' => ['name' => 'DEFLATE Stream', 'speed' => 'Fast', 'ratio' => 'High', 'level_range' => '1-9'],
            'gzip' => ['name' => 'GZIP Container', 'speed' => 'Fast', 'ratio' => 'High', 'level_range' => '1-9'],
            'raw' => ['name' => 'Raw Uncompressed', 'speed' => 'Sub-millisecond', 'ratio' => '1.0x', 'level_range' => 'N/A'],
        ];
    }
}
