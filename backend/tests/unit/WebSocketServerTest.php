<?php

use PHPUnit\Framework\TestCase;
use Atom\Sync\WebSocketServer;

/**
 * Phase 28 — WebSocketServer unit tests (5 tests).
 */
class WebSocketServerTest extends TestCase
{
    private WebSocketServer $server;

    protected function setUp(): void
    {
        $this->server = new WebSocketServer();
    }

    public function testCreateFrameGeneratesValidStructure(): void
    {
        $frame = $this->server->createFrame('chat:message', ['user' => 'Vichu', 'text' => 'Hello']);
        $this->assertArrayHasKey('frame_id', $frame);
        $this->assertSame('chat:message', $frame['event']);
        $this->assertSame('Vichu', $frame['payload']['user']);
        $this->assertArrayHasKey('timestamp', $frame);
    }

    public function testParseValidFrame(): void
    {
        $raw = json_encode(['event' => 'ping', 'payload' => ['ts' => 12345]]);
        $res = $this->server->parseFrame($raw);

        $this->assertTrue($res['valid']);
        $this->assertSame('ping', $res['frame']['event']);
    }

    public function testParseInvalidFrameReturnsError(): void
    {
        $res = $this->server->parseFrame('not-a-valid-json');
        $this->assertFalse($res['valid']);
        $this->assertArrayHasKey('error', $res);
    }

    public function testCreateHeartbeatPing(): void
    {
        $ping = $this->server->createHeartbeatPing();
        $this->assertSame('system:ping', $ping['event']);
        $this->assertSame('healthy', $ping['payload']['status']);
    }

    public function testBroadcastRecordsInEventHistory(): void
    {
        $this->server->broadcast('test:event_1', ['key' => 'val1']);
        $this->server->broadcast('test:event_2', ['key' => 'val2']);

        $history = $this->server->getEventHistory(5);
        $this->assertGreaterThanOrEqual(2, count($history));
        $this->assertSame('test:event_2', $history[0]['event']);
    }
}
