<?php

namespace Atom\Voice;

use Atom\Security\SecretRedactor;

/**
 * Audio Duplex Protocol — Phase 34
 *
 * Full-duplex binary and JSON frame serializer/parser for low-latency streaming
 * audio chunks (16kHz 16-bit PCM mono), lifecycle events, and barge-in signals.
 */
class AudioDuplexProtocol
{
    public const FRAME_START     = 'START';
    public const FRAME_CHUNK     = 'CHUNK';
    public const FRAME_INTERRUPT = 'INTERRUPT';
    public const FRAME_END       = 'END';

    public const MAX_CHUNK_BYTES = 524288; // 512 KB
    public const DEFAULT_SAMPLE_RATE = 16000;
    public const DEFAULT_CHANNELS = 1;

    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Creates an audio stream protocol frame packet.
     *
     * @param string $type Frame type (START, CHUNK, INTERRUPT, END).
     * @param int $sequence Monotonic sequence index.
     * @param string $payload Base64 or binary audio chunk.
     * @param array $metadata Audio format metadata (sample_rate, channels).
     * @return array Standardized frame structure.
     */
    public function createFrame(string $type, int $sequence, string $payload = '', array $metadata = []): array
    {
        $validTypes = [self::FRAME_START, self::FRAME_CHUNK, self::FRAME_INTERRUPT, self::FRAME_END];
        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException("Invalid frame type: '{$type}'");
        }

        if (strlen($payload) > self::MAX_CHUNK_BYTES) {
            throw new \InvalidArgumentException("Audio chunk exceeds maximum allowed size of " . self::MAX_CHUNK_BYTES . " bytes");
        }

        return [
            'type'        => $type,
            'sequence'    => max(0, $sequence),
            'payload'     => $payload,
            'sample_rate' => $metadata['sample_rate'] ?? self::DEFAULT_SAMPLE_RATE,
            'channels'    => $metadata['channels'] ?? self::DEFAULT_CHANNELS,
            'checksum'    => hash('crc32b', $payload),
            'timestamp'   => microtime(true),
        ];
    }

    /**
     * Parses and validates a received audio frame.
     *
     * @param array|string $rawFrame Array or JSON string frame.
     * @return array Validated frame.
     */
    public function parseFrame(array|string $rawFrame): array
    {
        if (is_string($rawFrame)) {
            $parsed = json_decode($rawFrame, true);
            if (!is_array($parsed)) {
                throw new \InvalidArgumentException('Invalid JSON audio frame');
            }
            $rawFrame = $parsed;
        }

        $requiredKeys = ['type', 'sequence'];
        foreach ($requiredKeys as $k) {
            if (!isset($rawFrame[$k])) {
                throw new \InvalidArgumentException("Missing required frame field: '{$k}'");
            }
        }

        $type = $rawFrame['type'];
        $validTypes = [self::FRAME_START, self::FRAME_CHUNK, self::FRAME_INTERRUPT, self::FRAME_END];
        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException("Unrecognized frame type: '{$type}'");
        }

        $payload = $rawFrame['payload'] ?? '';
        if (strlen($payload) > self::MAX_CHUNK_BYTES) {
            throw new \InvalidArgumentException("Audio chunk exceeds size limit");
        }

        // Verify checksum if provided
        if (isset($rawFrame['checksum']) && !empty($payload)) {
            $expected = hash('crc32b', $payload);
            if (!hash_equals($expected, $rawFrame['checksum'])) {
                throw new \RuntimeException('Audio frame checksum mismatch (corrupted data)');
            }
        }

        return [
            'type'        => $type,
            'sequence'    => (int)$rawFrame['sequence'],
            'payload'     => $payload,
            'sample_rate' => (int)($rawFrame['sample_rate'] ?? self::DEFAULT_SAMPLE_RATE),
            'channels'    => (int)($rawFrame['channels'] ?? self::DEFAULT_CHANNELS),
            'timestamp'   => (float)($rawFrame['timestamp'] ?? microtime(true)),
        ];
    }
}
