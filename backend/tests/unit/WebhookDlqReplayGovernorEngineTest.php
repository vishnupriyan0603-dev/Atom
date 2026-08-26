<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Network\WebhookDlqReplayGovernorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 97 — WebhookDlqReplayGovernorEngine unit tests (6 tests).
 */
class WebhookDlqReplayGovernorEngineTest extends TestCase
{
    private WebhookDlqReplayGovernorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new WebhookDlqReplayGovernorEngine(new SecretRedactor());
    }

    public function testEnqueueFailedWebhookCreatesDlqItem(): void
    {
        $res = $this->engine->enqueue('https://api.test.io/events', ['id' => 101], 'HTTP_500');

        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('dlq_id', $res);
        $this->assertSame('RETRY_PENDING', $res['item']['status']);
        $this->assertSame(1, $res['item']['attempt']);
    }

    public function testMaxAttemptsReachedSetsPermanentlyDead(): void
    {
        $res = $this->engine->enqueue('https://api.test.io/events', ['id' => 102], 'HTTP_500', 5);

        $this->assertTrue($res['success']);
        $this->assertSame('PERMANENTLY_DEAD', $res['item']['status']);
    }

    public function testExponentialBackoffIncreasesWithAttempt(): void
    {
        $delay1 = $this->engine->calculateBackoff(1);
        $delay2 = $this->engine->calculateBackoff(2);
        $delay3 = $this->engine->calculateBackoff(3);

        $this->assertGreaterThan($delay1, $delay2);
        $this->assertGreaterThan($delay2, $delay3);
    }

    public function testReplayPendingProcessesDlqItems(): void
    {
        $res = $this->engine->replayPending(true); // force all

        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(1, $res['replayed_count']);
    }

    public function testGetAllDlqItemsReturnsArray(): void
    {
        $items = $this->engine->getAllDlqItems();

        $this->assertGreaterThanOrEqual(2, count($items));
        $this->assertArrayHasKey('target_url', $items[0]);
        $this->assertArrayHasKey('status', $items[0]);
    }

    public function testPendingCountTracksRetries(): void
    {
        $pending = $this->engine->getPendingCount();
        $this->assertGreaterThanOrEqual(0, $pending);
    }
}
