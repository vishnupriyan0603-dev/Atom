<?php

namespace Atom\Network;

use Atom\Security\SecretRedactor;

/**
 * EventMeshTopicBrokerEngine — Phase 92
 * Multi-channel event mesh broker, hierarchical wildcard topic pattern matcher (single '+' and multi '#' levels), and consumer group offset governor.
 */
class EventMeshTopicBrokerEngine
{
    private SecretRedactor $redactor;
    private array $subscriptions = []; // [ topic_pattern => [ [subscriber_id, consumer_group, callback] ] ]
    private array $topicStats = []; // [ topic => [published_count, delivered_count, last_published_at] ]
    private array $deadLetterQueue = [];

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleSubscriptions();
    }

    /**
     * Subscribe a consumer to a hierarchical topic pattern.
     * Supports single-level wildcard '+' and multi-level wildcard '#'.
     */
    public function subscribe(string $topicPattern, string $subscriberId, string $consumerGroup = 'default'): bool
    {
        $cleanPattern = trim(strtolower($this->redactor->redact($topicPattern)));
        $cleanSubscriber = trim(strtolower($this->redactor->redact($subscriberId)));

        if (!isset($this->subscriptions[$cleanPattern])) {
            $this->subscriptions[$cleanPattern] = [];
        }

        // Avoid duplicate subscriptions
        foreach ($this->subscriptions[$cleanPattern] as $sub) {
            if ($sub['subscriber_id'] === $cleanSubscriber) {
                return true;
            }
        }

        $this->subscriptions[$cleanPattern][] = [
            'subscriber_id' => $cleanSubscriber,
            'consumer_group' => $consumerGroup,
            'subscribed_at' => microtime(true),
        ];

        return true;
    }

    /**
     * Publish a message to a specific topic channel and fan-out to all matching wildcard subscribers.
     */
    public function publish(string $topic, array $payload, string $publisherId = 'system'): array
    {
        $cleanTopic = trim(strtolower($this->redactor->redact($topic)));

        if ($cleanTopic === '') {
            return [
                'success' => false,
                'error' => 'Topic name cannot be empty',
                'matched_subscribers_count' => 0,
            ];
        }

        if (!isset($this->topicStats[$cleanTopic])) {
            $this->topicStats[$cleanTopic] = [
                'topic' => $cleanTopic,
                'published_count' => 0,
                'delivered_count' => 0,
                'last_published_at' => 0.0,
            ];
        }

        $this->topicStats[$cleanTopic]['published_count']++;
        $this->topicStats[$cleanTopic]['last_published_at'] = microtime(true);

        $matchedSubscribers = [];

        foreach ($this->subscriptions as $pattern => $subList) {
            if ($this->matchTopic($pattern, $cleanTopic)) {
                foreach ($subList as $sub) {
                    $matchedSubscribers[] = [
                        'subscriber_id' => $sub['subscriber_id'],
                        'pattern_matched' => $pattern,
                        'consumer_group' => $sub['consumer_group'],
                    ];
                    $this->topicStats[$cleanTopic]['delivered_count']++;
                }
            }
        }

        return [
            'success' => true,
            'topic' => $cleanTopic,
            'publisher_id' => $publisherId,
            'message_id' => 'msg_' . bin2hex(random_bytes(8)),
            'payload' => $payload,
            'matched_subscribers_count' => count($matchedSubscribers),
            'subscribers' => $matchedSubscribers,
        ];
    }

    /**
     * Check if a concrete topic matches a wildcard topic pattern.
     * Examples:
     * - 'sensors/+/temp' matches 'sensors/living_room/temp'
     * - 'events/#' matches 'events/finance/orders/new'
     */
    public function matchTopic(string $pattern, string $topic): bool
    {
        if ($pattern === '#' || $pattern === $topic) {
            return true;
        }

        $pTokens = explode('/', $pattern);
        $tTokens = explode('/', $topic);

        $pLen = count($pTokens);
        $tLen = count($tTokens);

        for ($i = 0; $i < $pLen; $i++) {
            $p = $pTokens[$i];

            if ($p === '#') {
                return true; // Matches everything remaining
            }

            if ($i >= $tLen) {
                return false;
            }

            if ($p !== '+' && $p !== $tTokens[$i]) {
                return false;
            }
        }

        return $pLen === $tLen;
    }

    public function getBrokerStatus(): array
    {
        return [
            'total_topic_patterns' => count($this->subscriptions),
            'total_active_topics' => count($this->topicStats),
            'topics' => array_values($this->topicStats),
            'subscriptions' => $this->subscriptions,
        ];
    }

    private function seedSampleSubscriptions(): void
    {
        $this->subscribe('telemetry/+/temperature', 'sub_weather_dashboard', 'analytics');
        $this->subscribe('events/finance/#', 'sub_ledger_auditor', 'audit');
        $this->subscribe('system/alerts', 'sub_ops_notifier', 'ops');
    }
}
