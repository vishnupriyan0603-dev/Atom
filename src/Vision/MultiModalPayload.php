<?php

namespace Atom\Vision;

/**
 * MultiModalPayload — Value object for image and multi-modal attachments.
 */
class MultiModalPayload
{
    public readonly string $mimeType;
    public readonly string $base64Data;
    public readonly string $fileName;
    public readonly int $sizeBytes;

    public function __construct(string $base64Data, string $mimeType = 'image/png', string $fileName = 'image.png')
    {
        $this->base64Data = $base64Data;
        $this->mimeType = strtolower(trim($mimeType));
        $this->fileName = $fileName;
        $this->sizeBytes = (int) (strlen($base64Data) * 0.75);
    }

    /**
     * Create payload from a local file path.
     */
    public static function fromFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $raw = file_get_contents($filePath);
        if ($raw === false) {
            throw new \RuntimeException("Unable to read file: {$filePath}");
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };

        return new self(base64_encode($raw), $mime, basename($filePath));
    }

    public function toArray(): array
    {
        return [
            'mime_type' => $this->mimeType,
            'file_name' => $this->fileName,
            'size_bytes' => $this->sizeBytes,
            'is_image' => str_starts_with($this->mimeType, 'image/'),
        ];
    }
}
