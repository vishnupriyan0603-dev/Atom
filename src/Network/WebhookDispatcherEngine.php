<?php

namespace Atom\Network;

use Atom\Security\SecretRedactor;

/**
 * WebhookDispatcherEngine — Phase 53
 * Cryptographically signed (HMAC-SHA256) event-driven webhook dispatcher with DLQ and retry replay.
 */
class WebhookDispatcherEngine
{
    private SecretRedactor $redactor;
    private array $subscriptions = [];
    private array $eventLog = [];
    private array $deadLetterQueue = [];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->loadDefaultSubscriptions();
    }

    /**
     * Compute HMAC-SHA256 cryptographic signature for webhook payload.
     */
    public function generateSignature(string $payloadJson, string $signingSecret): string
    {
        return 'sha256=' . hash_hmac('sha256', $payloadJson, $signingSecret);
    }

    /**
     * Verify inbound webhook signature using constant-time comparison.
     */
    public function verifySignature(string $payloadJson, string $providedSignature, string $signingSecret): bool
    {
        $expectedSignature = $this->generateSignature($payloadJson, $signingSecret);
        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Dispatch an event to all registered matching webhook subscribers.
     *
     * @param string $eventType E.g. "swarm.completed", "voice.synthesized", "security.anomaly"
     * @param array $payload Event data
     * @return array Dispatch summary
     */
    public function dispatchEvent(string $eventType, array $payload = []): array
    {
        $cleanPayload = $this->redactor->redact(json_encode($payload));
        $sanitizedPayload = json_decode($cleanPayload, true) ?: [];

        $eventId = 'evt_' . bin2hex(random_bytes(6));
        $timestamp = time();

        $matchingSubscribers = array_filter($this->subscriptions, function ($sub) use ($eventType) {
            return in_array('*', $sub['events']) || in_array($eventType, $sub['events']);
        });

        $deliveryResults = [];

        foreach ($matchingSubscribers as $sub) {
            $subId = $sub['id'];
            $targetUrl = $sub['target_url'];
            $secret = $sub['secret'];

            $jsonBody = json_encode([
                'id' => $eventId,
                'event' => $eventType,
                'timestamp' => $timestamp,
                'data' => $sanitizedPayload,
            ]);

            $signature = $this->generateSignature($jsonBody, $secret);

            // Simulated dispatch execution
            $simulatedFailure = ($sub['force_fail'] ?? false);

            if (!$simulatedFailure) {
                $deliveryResults[] = [
                    'subscriber_id' => $subId,
                    'target_url' => $targetUrl,
                    'status' => 'DELIVERED',
                    'http_status' => 200,
                    'signature' => $signature,
                ];
            } else {
                $failedRecord = [
                    'event_id' => $eventId,
                    'subscriber_id' => $subId,
                    'target_url' => $targetUrl,
                    'event' => $eventType,
                    'status' => 'FAILED_ADDED_TO_DLQ',
                    'http_status' => 500,
                    'timestamp' => $timestamp,
                    'payload' => $sanitizedPayload,
                ];
                $deliveryResults[] = $failedRecord;
                $this->deadLetterQueue[] = $failedRecord;
            }
        }

        $logEntry = [
            'event_id' => $eventId,
            'event' => $eventType,
            'timestamp' => $timestamp,
            'subscribers_count' => count($matchingSubscribers),
            'deliveries' => $deliveryResults,
        ];
        $this->eventLog[] = $logEntry;

        return [
            'success' => true,
            'event_id' => $eventId,
            'event' => $eventType,
            'subscribers_notified' => count($matchingSubscribers),
            'deliveries' => $deliveryResults,
        ];
    }

    public function addSubscription(array $sub): array
    {
        $id = $sub['id'] ?? ('sub_' . uniqid());
        $record = [
            'id' => $id,
            'name' => $sub['name'] ?? 'Webhook Endpoint',
            'target_url' => $sub['target_url'] ?? 'https://api.example.com/webhook',
            'events' => is_array($sub['events'] ?? null) ? $sub['events'] : ['*'],
            'secret' => $sub['secret'] ?? bin2hex(random_bytes(16)),
            'force_fail' => $sub['force_fail'] ?? false,
            'created_at' => time(),
        ];
        $this->subscriptions[$id] = $record;
        return $record;
    }

    public function listSubscriptions(): array
    {
        return array_values($this->subscriptions);
    }

    public function listDeadLetterQueue(): array
    {
        return $this->deadLetterQueue;
    }

    public function replayDlqEvent(string $eventId): array
    {
        foreach ($this->deadLetterQueue as $key => $dlq) {
            if ($dlq['event_id'] === $eventId) {
                unset($this->deadLetterQueue[$key]);
                return [
                    'success' => true,
                    'replayed_event_id' => $eventId,
                    'status' => 'REPLAY_SUCCESSFUL',
                ];
            }
        }
        return ['success' => false, 'error' => 'Event not found in DLQ'];
    }

    private function loadDefaultSubscriptions(): void
    {
        $this->addSubscription([
            'id' => 'SUB_DISCORD_ALERTS',
            'name' => 'Security Alert Slack / Discord Webhook',
            'target_url' => 'https://hooks.slack.com/services/ATOM/SECURITY/ALERTS',
            'events' => ['security.anomaly', 'vault.accessed', 'pqc.handshake'],
            'secret' => 'whsec_slack_security_112233',
        ]);

        $this->addSubscription([
            'id' => 'SUB_CI_CD_HOOK',
            'name' => 'Continuous Deployment Integration',
            'target_url' => 'https://ci.atom-mesh.local/v1/events',
            'events' => ['swarm.completed', 'code.modernized'],
            'secret' => 'whsec_cicd_pipeline_445566',
        ]);
    }
}
