<?php

namespace App\Controllers\Api;

use Atom\Network\EventMeshTopicBrokerEngine;

/**
 * EventMesh API Controller — Phase 92
 */
class EventMesh extends BaseApiController
{
    private static ?EventMeshTopicBrokerEngine $engine = null;

    private function getEngine(): EventMeshTopicBrokerEngine
    {
        if (self::$engine === null) {
            self::$engine = new EventMeshTopicBrokerEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/network/mesh/publish
     */
    public function publish()
    {
        $json = $this->request->getJSON(true) ?? [];
        $topic = $json['topic'] ?? 'telemetry/node_alpha/temperature';
        $payload = $json['payload'] ?? ['value' => 24.5, 'unit' => 'celsius', 'timestamp' => microtime(true)];
        $publisher = $json['publisher_id'] ?? 'gateway_sensor_1';

        $engine = $this->getEngine();
        $res = $engine->publish($topic, $payload, $publisher);

        return $this->respondSuccess($res, 'Message published to event mesh');
    }

    /**
     * POST /api/network/mesh/subscribe
     */
    public function subscribe()
    {
        $json = $this->request->getJSON(true) ?? [];
        $pattern = $json['topic_pattern'] ?? 'telemetry/+/temperature';
        $subscriber = $json['subscriber_id'] ?? 'client_ui_listener';
        $group = $json['consumer_group'] ?? 'frontend';

        $engine = $this->getEngine();
        $ok = $engine->subscribe($pattern, $subscriber, $group);

        return $this->respondSuccess(['subscribed' => $ok, 'pattern' => $pattern, 'subscriber' => $subscriber], 'Subscribed to topic pattern');
    }

    /**
     * GET /api/network/mesh/topics
     */
    public function topics()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getBrokerStatus(), 'Event mesh status & topics');
    }
}
