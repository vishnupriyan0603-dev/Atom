<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\MultiTurnContextMemoryEngine;
use Atom\Security\SecretRedactor;

/**
 * Security and safety pass test suite for Atom Brain Phase 2.
 */
class AtomBrainPhase2SecurityPassTest extends TestCase
{
    private string $tempStorage;
    private MultiTurnContextMemoryEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempStorage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_sec_memory_' . uniqid() . '.json';
        $this->engine = new MultiTurnContextMemoryEngine(new SecretRedactor(), $this->tempStorage);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempStorage)) {
            @unlink($this->tempStorage);
        }
        parent::tearDown();
    }

    public function testSecretRedactionInMemory(): void
    {
        $secretMsg = 'My production secret is sk-proj-1234567890abcdef1234567890abcdef and token = "ghp_123456789012345678901234567890123456"';
        $turn = $this->engine->recordTurn($secretMsg, 'Noted credentials.');

        $this->assertStringNotContainsString('sk-proj-1234567890abcdef1234567890abcdef', $turn['user']);
        $this->assertStringNotContainsString('ghp_123456789012345678901234567890123456', $turn['user']);

        // Fact storage redacts secrets
        $factRes = $this->engine->storeFact('secrets', 'My password is secret = "SuperSecretPass123!" with token sk-ant-1234567890abcdef1234567890abcdef');
        $this->assertTrue($factRes['success']);
        $this->assertStringNotContainsString('sk-ant-1234567890abcdef1234567890abcdef', $factRes['stored']['fact']);
        $this->assertStringNotContainsString('SuperSecretPass123!', $factRes['stored']['fact']);
    }

    public function testHighThroughputTurnIngestion(): void
    {
        $start = microtime(true);
        for ($i = 0; $i < 50; $i++) {
            $this->engine->recordTurn("Test turn user {$i}", "Test turn assistant {$i}");
        }
        $elapsed = microtime(true) - $start;

        $status = $this->engine->getMemoryStatus();
        $this->assertLessThanOrEqual(30, $status['working_memory_count']);
        $this->assertLessThan(2.0, $elapsed, 'High throughput memory insertion should complete rapidly');
    }

    public function testMemoryPersistenceAcrossInstances(): void
    {
        $this->engine->storeFact('rule', 'Phase 2 persistent fact test');

        // New instance with same storage file
        $engine2 = new MultiTurnContextMemoryEngine(new SecretRedactor(), $this->tempStorage);
        $status = $engine2->getMemoryStatus();

        $this->assertCount(1, $status['facts']);
        $this->assertEquals('Phase 2 persistent fact test', $status['facts'][0]['fact']);
    }

    public function testClearMemorySecurity(): void
    {
        $this->engine->storeFact('pref', 'User custom configuration');
        $this->engine->recordTurn('Hello', 'Hi');

        $this->engine->clearMemory(false); // clear all

        $status = $this->engine->getMemoryStatus();
        $this->assertEquals(0, $status['working_memory_count']);
        $this->assertEquals(0, $status['facts_count']);
    }

    public function testGracefulHandlingOfMalformedAndSpecialCharacters(): void
    {
        $malformed = "Null byte \0 and emojis 🧠⚡🤖 and <script>alert(1)</script> and \x80\xFF";
        $turn = $this->engine->recordTurn($malformed, "Handled cleanly.");

        $this->assertNotEmpty($turn['id']);
        $this->assertIsArray($turn['entities']);

        $res = $this->engine->storeFact('xss', '<svg onload=alert(1)>');
        $this->assertTrue($res['success']);
    }
}
