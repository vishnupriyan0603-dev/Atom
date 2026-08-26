<?php

namespace App\Controllers\Api;

use Atom\Network\WebhookDlqReplayGovernorEngine;

/**
 * WebhookDlq API Controller — Phase 97
 */
class WebhookDlq extends BaseApiController
{
    private static ?WebhookDlqReplayGovernorEngine $engine = null;

    private function getEngine(): WebhookDlqReplayGovernorEngine
    {
        if (self::$engine === null) {
            self::$engine = new WebhookDlqReplayGovernorEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/network/dlq/enqueue
     */
    public function enqueue()
    {
        $json = $this->request->getJSON(true) ?? [];
        $url = $json['target_url'] ?? 'https://api.partner.io/webhook/callback';
        $payload = $json['payload'] ?? ['event' => 'invoice.generated', 'amount' => 199.0];
        $error = $json['error'] ?? 'HTTP_500_INTERNAL_SERVER_ERROR';
        $attempt = (int)($json['attempt'] ?? 1);

        $engine = $this->getEngine();
        $res = $engine->enqueue($url, $payload, $error, $attempt);

        return $this->respondSuccess($res, 'Failed webhook delivery enqueued to DLQ');
    }

    /**
     * POST /api/network/dlq/replay
     */
    public function replay()
    {
        $json = $this->request->getJSON(true) ?? [];
        $force = (bool)($json['force_all'] ?? true);

        $engine = $this->getEngine();
        $res = $engine->replayPending($force);

        return $this->respondSuccess($res, 'Pending DLQ webhooks replayed');
    }

    /**
     * GET /api/network/dlq/items
     */
    public function items()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess([
            'total_items' => count($engine->getAllDlqItems()),
            'pending_count' => $engine->getPendingCount(),
            'items' => $engine->getAllDlqItems(),
        ], 'DLQ items & status');
    }
}
