<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\EventMeshTopicBrokerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 92 — EventMeshTopicBrokerEngine unit tests (6 tests).
 */
class EventMeshTopicBrokerEngineTest extends TestCase
{
    private EventMeshTopicBrokerEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new EventMeshTopicBrokerEngine(new SecretRedactor());
    }

    public function testSingleLevelWildcardMatching(): void
    {
        $this->engine->subscribe('iot/+/temperature', 'sub_temp_reader');

        $res1 = $this->engine->publish('iot/kitchen/temperature', ['temp' => 22.5]);
        $res2 = $this->engine->publish('iot/bedroom/temperature', ['temp' => 19.8]);
        $res3 = $this->engine->publish('iot/kitchen/humidity', ['humidity' => 60]);

        $this->assertTrue($res1['success']);
        $this->assertSame(1, $res1['matched_subscribers_count']);

        $this->assertTrue($res2['success']);
        $this->assertSame(1, $res2['matched_subscribers_count']);

        $this->assertTrue($res3['success']);
        $this->assertSame(0, $res3['matched_subscribers_count']); // Did not match /temperature
    }

    public function testMultiLevelWildcardMatching(): void
    {
        $this->engine->subscribe('orders/#', 'sub_audit_logger');

        $res1 = $this->engine->publish('orders/eu/create', ['id' => 101]);
        $res2 = $this->engine->publish('orders/us/west/payment/settled', ['id' => 102]);

        $this->assertTrue($res1['success']);
        $this->assertSame(1, $res1['matched_subscribers_count']);

        $this->assertTrue($res2['success']);
        $this->assertSame(1, $res2['matched_subscribers_count']);
    }

    public function testDirectExactTopicMatch(): void
    {
        $this->engine->subscribe('system/maintenance', 'sub_ops');
        $res = $this->engine->publish('system/maintenance', ['mode' => 'active']);

        $this->assertTrue($res['success']);
        $this->assertSame(1, $res['matched_subscribers_count']);
        $this->assertSame('sub_ops', $res['subscribers'][0]['subscriber_id']);
    }

    public function testDuplicateSubscriptionIdPrevented(): void
    {
        $this->assertTrue($this->engine->subscribe('alerts/critical', 'sub_phone_alert'));
        $this->assertTrue($this->engine->subscribe('alerts/critical', 'sub_phone_alert'));

        $res = $this->engine->publish('alerts/critical', ['level' => 'HIGH']);
        $this->assertSame(1, $res['matched_subscribers_count']);
    }

    public function testEmptyTopicPublishFailsGracefully(): void
    {
        $res = $this->engine->publish('', ['data' => 123]);
        $this->assertFalse($res['success']);
        $this->assertSame(0, $res['matched_subscribers_count']);
    }

    public function testGetBrokerStatusReportsActiveChannels(): void
    {
        $status = $this->engine->getBrokerStatus();

        $this->assertGreaterThanOrEqual(3, $status['total_topic_patterns']);
        $this->assertArrayHasKey('subscriptions', $status);
        $this->assertArrayHasKey('topics', $status);
    }
}
