<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\WebhookDispatcherEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 53 — WebhookDispatcherEngine unit tests (6 tests).
 */
class WebhookDispatcherEngineTest extends TestCase
{
    private WebhookDispatcherEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new WebhookDispatcherEngine(new SecretRedactor());
    }

    public function testHmacSha256SignatureGeneration(): void
    {
        $payload = json_encode(['event' => 'ping']);
        $secret = 'test_secret_123';

        $sig = $this->engine->generateSignature($payload, $secret);

        $this->assertStringStartsWith('sha256=', $sig);
        $this->assertTrue($this->engine->verifySignature($payload, $sig, $secret));
    }

    public function testSignatureVerificationRejectsTamperedPayload(): void
    {
        $payload = json_encode(['event' => 'ping']);
        $tampered = json_encode(['event' => 'malicious_ping']);
        $secret = 'test_secret_123';

        $sig = $this->engine->generateSignature($payload, $secret);

        $this->assertFalse($this->engine->verifySignature($tampered, $sig, $secret));
    }

    public function testDispatchEventNotifiesSubscribers(): void
    {
        $res = $this->engine->dispatchEvent('security.anomaly', ['threat' => 'high']);

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(0, $res['subscribers_notified']);
        $this->assertSame('DELIVERED', $res['deliveries'][0]['status']);
    }

    public function testAddCustomSubscription(): void
    {
        $sub = $this->engine->addSubscription([
            'id' => 'CUSTOM_HOOK_1',
            'name' => 'Custom Analytics Webhook',
            'target_url' => 'https://analytics.custom.com/hook',
            'events' => ['custom.metric'],
        ]);

        $this->assertSame('CUSTOM_HOOK_1', $sub['id']);
        $this->assertContains('CUSTOM_HOOK_1', array_column($this->engine->listSubscriptions(), 'id'));
    }

    public function testDeadLetterQueueRecordingAndReplay(): void
    {
        $this->engine->addSubscription([
            'id' => 'FAILING_SUB',
            'name' => 'Failing Webhook',
            'target_url' => 'https://broken.endpoint.com',
            'events' => ['test.fail'],
            'force_fail' => true,
        ]);

        $this->engine->dispatchEvent('test.fail', ['data' => 'failure_test']);
        $dlq = $this->engine->listDeadLetterQueue();

        $this->assertNotEmpty($dlq);
        $failedEventId = $dlq[0]['event_id'];

        $replay = $this->engine->replayDlqEvent($failedEventId);
        $this->assertTrue($replay['success']);
        $this->assertSame('REPLAY_SUCCESSFUL', $replay['status']);
    }

    public function testReplayUnknownEventFailsGracefully(): void
    {
        $res = $this->engine->replayDlqEvent('non_existent_evt');
        $this->assertFalse($res['success']);
    }
}
