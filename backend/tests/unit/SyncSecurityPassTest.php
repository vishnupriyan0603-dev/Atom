<?php

use PHPUnit\Framework\TestCase;
use Atom\Sync\SyncEngine;
use Atom\Sync\StateReplicationEngine;
use Atom\Sync\WebSocketServer;

/**
 * Phase 28 — SyncSecurityPassTest (5 tests).
 *
 * Enforces safety boundaries for Real-Time Sync & Event Streaming:
 * - Secret redaction in WebSocket broadcast frames
 * - Secret redaction in CRDT state deltas
 * - Device peer registry isolation & unregistration
 * - Safe handling of malformed frame strings
 * - Vector clock integrity protection
 */
class SyncSecurityPassTest extends TestCase
{
    public function testSecretRedactionInBroadcastPayload(): void
    {
        $server = new WebSocketServer();
        $frame = $server->createFrame('config:updated', [
            'api_key' => 'sk-1234567890abcdef1234567890abcdef',
            'status' => 'ok',
        ]);

        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', json_encode($frame));
        $this->assertStringContainsString('[REDACTED]', json_encode($frame));
    }

    public function testSecretRedactionInReplicatedDeltas(): void
    {
        $engine = new StateReplicationEngine();
        $delta = $engine->recordDelta('credential', 'cred_01', [
            'token' => 'Bearer sk-1234567890abcdef1234567890abcdef',
        ]);

        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', json_encode($delta));
        $this->assertStringContainsString('[REDACTED]', json_encode($delta));
    }

    public function testPeerUnregistrationRemovesFromActiveList(): void
    {
        $sync = new SyncEngine();
        $sync->registerPeer('temp_device', 'mobile_flutter', 'Temp Phone');

        $this->assertNotNull($sync->getPeerRegistry()->getPeer('temp_device'));

        $removed = $sync->getPeerRegistry()->unregister('temp_device');
        $this->assertTrue($removed);
        $this->assertNull($sync->getPeerRegistry()->getPeer('temp_device'));
    }

    public function testMalformedJsonInParseFrameDoesNotThrow(): void
    {
        $server = new WebSocketServer();
        $res = $server->parseFrame('{ malformed json');

        $this->assertFalse($res['valid']);
        $this->assertArrayHasKey('error', $res);
    }

    public function testVectorClockNeverRegresses(): void
    {
        $engine = new StateReplicationEngine();
        $c1 = $engine->recordDelta('type', 'id1', [])['clock'];
        $c2 = $engine->recordDelta('type', 'id2', [])['clock'];
        $c3 = $engine->recordDelta('type', 'id3', [])['clock'];

        $this->assertGreaterThan($c1, $c2);
        $this->assertGreaterThan($c2, $c3);
    }
}
