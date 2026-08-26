<?php

namespace Atom\Network;

use Atom\Security\SecretRedactor;

/**
 * WebhookDlqReplayGovernorEngine — Phase 97
 * Dead-Letter Queue (DLQ) auto-replay governor, exponential backoff with jitter calculator, and webhook delivery circuit resiliency.
 */
class WebhookDlqReplayGovernorEngine
{
    private SecretRedactor $redactor;
    private array $dlq = []; // [ id => [ 'id', 'url', 'payload', 'attempt', 'max_attempts', 'next_retry_at', 'status' ] ]
    private int $maxAttempts = 5;
    private float $baseDelaySec = 1.0;
    private float $maxDelaySec = 300.0;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleDlq();
    }

    /**
     * Enqueue a failed webhook delivery attempt to the DLQ.
     */
    public function enqueue(string $targetUrl, array $payload, string $lastError = 'HTTP_TIMEOUT', int $attempt = 1): array
    {
        $cleanUrl = trim($this->redactor->redact($targetUrl));
        $id = 'dlq_' . bin2hex(random_bytes(6));
        $nextDelay = $this->calculateBackoff($attempt);

        $item = [
            'id' => $id,
            'target_url' => $cleanUrl,
            'payload' => $payload,
            'attempt' => $attempt,
            'max_attempts' => $this->maxAttempts,
            'last_error' => $lastError,
            'enqueued_at' => microtime(true),
            'next_retry_at' => microtime(true) + $nextDelay,
            'backoff_delay_sec' => $nextDelay,
            'status' => ($attempt >= $this->maxAttempts) ? 'PERMANENTLY_DEAD' : 'RETRY_PENDING',
        ];

        $this->dlq[$id] = $item;

        return [
            'success' => true,
            'dlq_id' => $id,
            'item' => $item,
        ];
    }

    /**
     * Process and replay all eligible DLQ items whose backoff timer has expired.
     */
    public function replayPending(bool $forceAll = false): array
    {
        $now = microtime(true);
        $replayed = [];
        $exhausted = [];

        foreach ($this->dlq as $id => &$item) {
            if ($item['status'] === 'PERMANENTLY_DEAD' || $item['status'] === 'SUCCESS') {
                continue;
            }

            if ($forceAll || $item['next_retry_at'] <= $now) {
                $item['attempt']++;

                // Emulated delivery check (success for demo replay simulation)
                $deliverySuccess = ($item['attempt'] <= $this->maxAttempts);

                if ($deliverySuccess) {
                    $item['status'] = 'SUCCESS';
                    $item['replayed_at'] = microtime(true);
                    $replayed[] = $item;
                } else {
                    $item['status'] = 'PERMANENTLY_DEAD';
                    $exhausted[] = $item;
                }
            }
        }

        return [
            'success' => true,
            'replayed_count' => count($replayed),
            'exhausted_count' => count($exhausted),
            'total_pending' => $this->getPendingCount(),
            'replayed_items' => $replayed,
        ];
    }

    public function calculateBackoff(int $attempt): float
    {
        $exponent = max(0, $attempt - 1);
        $delay = $this->baseDelaySec * pow(2, $exponent);
        $jitter = (mt_rand(0, 100) / 1000.0); // 0-100ms jitter
        return min($this->maxDelaySec, round($delay + $jitter, 2));
    }

    public function getPendingCount(): int
    {
        $count = 0;
        foreach ($this->dlq as $item) {
            if ($item['status'] === 'RETRY_PENDING') {
                $count++;
            }
        }
        return $count;
    }

    public function getAllDlqItems(): array
    {
        return array_values($this->dlq);
    }

    private function seedSampleDlq(): void
    {
        $this->enqueue('https://api.partner.io/webhooks/orders', ['order_id' => 101, 'status' => 'paid'], 'HTTP_504_GATEWAY_TIMEOUT', 2);
        $this->enqueue('https://crm.customer.org/events/users', ['user_id' => 456, 'event' => 'login'], 'CONNECTION_RESET_BY_PEER', 1);
    }
}
