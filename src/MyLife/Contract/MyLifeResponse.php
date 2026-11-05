<?php

declare(strict_types=1);

namespace Atom\MyLife\Contract;

/**
 * Standardized response envelope for all MyLife OS phone server requests.
 */
class MyLifeResponse
{
    private bool $success;
    private array $data;
    private ?string $error;
    private int $statusCode;
    private float $latencyMs;
    private ?string $deviceInfo;

    public function __construct(
        bool $success,
        array $data = [],
        ?string $error = null,
        int $statusCode = 200,
        float $latencyMs = 0.0,
        ?string $deviceInfo = null
    ) {
        $this->success    = $success;
        $this->data       = $data;
        $this->error      = $error;
        $this->statusCode = $statusCode;
        $this->latencyMs  = $latencyMs;
        $this->deviceInfo = $deviceInfo;
    }

    public static function success(array $data, float $latencyMs = 0.0, ?string $deviceInfo = 'Samsung Galaxy M31'): self
    {
        return new self(true, $data, null, 200, $latencyMs, $deviceInfo);
    }

    public static function failure(string $error, int $statusCode = 500, float $latencyMs = 0.0): self
    {
        return new self(false, [], $error, $statusCode, $latencyMs, null);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getLatencyMs(): float
    {
        return $this->latencyMs;
    }

    public function getDeviceInfo(): ?string
    {
        return $this->deviceInfo;
    }

    public function toArray(): array
    {
        return [
            'success'     => $this->success,
            'data'        => $this->data,
            'error'       => $this->error,
            'status_code' => $this->statusCode,
            'latency_ms'  => $this->latencyMs,
            'device'      => $this->deviceInfo,
        ];
    }
}
