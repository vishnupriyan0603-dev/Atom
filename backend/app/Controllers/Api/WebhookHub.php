<?php

namespace App\Controllers\Api;

use Atom\Network\WebhookDispatcherEngine;

/**
 * WebhookHub API Controller — Phase 53
 */
class WebhookHub extends BaseApiController
{
    private static ?WebhookDispatcherEngine $engine = null;

    private function getEngine(): WebhookDispatcherEngine
    {
        if (self::$engine === null) {
            self::$engine = new WebhookDispatcherEngine();
        }
        return self::$engine;
    }

    /**
     * GET /api/webhooks/subscriptions
     */
    public function listSubscriptions()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess([
            'total' => count($engine->listSubscriptions()),
            'subscriptions' => $engine->listSubscriptions(),
        ], 'Webhook subscriptions listed');
    }

    /**
     * POST /api/webhooks/subscriptions
     */
    public function createSubscription()
    {
        $json = $this->request->getJSON(true) ?? [];
        if (empty($json['name']) || empty($json['target_url'])) {
            return $this->respondError('Name and target_url are required', 400);
        }

        $engine = $this->getEngine();
        $sub = $engine->addSubscription($json);

        return $this->respondSuccess(['subscription' => $sub], 'Webhook subscription registered');
    }

    /**
     * POST /api/webhooks/dispatch
     */
    public function dispatch()
    {
        $json = $this->request->getJSON(true) ?? [];
        $event = $json['event'] ?? 'system.ping';
        $payload = $json['payload'] ?? [];

        $engine = $this->getEngine();
        $result = $engine->dispatchEvent($event, $payload);

        return $this->respondSuccess($result, 'Webhook event dispatched');
    }

    /**
     * GET /api/webhooks/dlq
     */
    public function listDlq()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess([
            'total_dlq' => count($engine->listDeadLetterQueue()),
            'dead_letter_events' => $engine->listDeadLetterQueue(),
        ], 'Dead-letter queue events');
    }

    /**
     * POST /api/webhooks/dlq/replay
     */
    public function replayDlq()
    {
        $json = $this->request->getJSON(true) ?? [];
        $eventId = $json['event_id'] ?? '';

        $engine = $this->getEngine();
        $res = $engine->replayDlqEvent($eventId);

        return $this->respondSuccess($res, 'DLQ event replayed');
    }
}
