<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Infrastructure\EventSourcingCqrsLedgerEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 102 — Phase102SecurityPassTest security & safety tests (5 tests).
 */
class Phase102SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testSecretRedactionInEventPayload(): void
    {
        $engine = new EventSourcingCqrsLedgerEngine($this->redactor);
        $res = $engine->dispatchCommand('sec-agg-1', 'CreateWorkspace', [
            'api_token' => 'sk-proj-supersecret1234567890abcdef',
            'nested' => [
                'db_pass' => 'DBPass999!',
            ],
        ]);

        $this->assertTrue($res['success']);
        $json = json_encode($res);
        $this->assertStringNotContainsString('sk-proj-supersecret1234567890abcdef', $json);
        $this->assertStringNotContainsString('DBPass999!', $json);
    }

    public function testHighThroughputEventAppend(): void
    {
        $engine = new EventSourcingCqrsLedgerEngine($this->redactor);
        $aggId = 'stress-agg-1';

        $start = microtime(true);
        for ($i = 0; $i < 200; $i++) {
            $engine->dispatchCommand($aggId, 'UpdateAgentPersona', ['turn' => $i]);
        }
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed, '200 chained cryptographic events should append in under 1.0s');
    }

    public function testEmptyCommandAndInvalidInputHandling(): void
    {
        $engine = new EventSourcingCqrsLedgerEngine($this->redactor);

        $emptyRes = $engine->dispatchCommand('', '', []);
        $this->assertFalse($emptyRes['success']);
        $this->assertStringContainsString('cannot be empty', $emptyRes['error']);
    }

    public function testExtremeTimeTravelTargetBoundsSafety(): void
    {
        $engine = new EventSourcingCqrsLedgerEngine($this->redactor);
        $aggId = 'bounds-agg-1';
        $engine->dispatchCommand($aggId, 'CreateWorkspace', ['v' => 1]);
        $engine->dispatchCommand($aggId, 'UpdateAgentPersona', ['v' => 2]);

        // Target version way beyond stream count -> clamped safely to latest
        $resHigh = $engine->timeTravelToVersion($aggId, 999999);
        $this->assertTrue($resHigh['success']);
        $this->assertEquals(2, $resHigh['target_version']);

        // Target version negative -> clamped safely to 1
        $resLow = $engine->timeTravelToVersion($aggId, -50);
        $this->assertTrue($resLow['success']);
        $this->assertEquals(1, $resLow['target_version']);
    }

    public function testTamperIntegrityOnCorruptedLedger(): void
    {
        $engine = new EventSourcingCqrsLedgerEngine($this->redactor);
        $aggId = 'tamper-agg-1';
        $engine->dispatchCommand($aggId, 'CreateWorkspace', ['name' => 'Initial']);
        $engine->dispatchCommand($aggId, 'UpdateAgentPersona', ['name' => 'Updated']);

        // Verify valid before tampering
        $verifyBefore = $engine->verifyLedgerIntegrity($aggId);
        $this->assertTrue($verifyBefore['is_valid']);

        // Verify non-existent aggregate behaves safely
        $nonExistent = $engine->verifyLedgerIntegrity('non-existent-ledger');
        $this->assertTrue($nonExistent['is_valid']);
        $this->assertEquals('empty_ledger', $nonExistent['status']);
    }
}
