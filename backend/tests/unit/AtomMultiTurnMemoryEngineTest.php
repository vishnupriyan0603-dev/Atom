<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\MultiTurnContextMemoryEngine;
use Atom\Security\SecretRedactor;

/**
 * Unit test suite for MultiTurnContextMemoryEngine (Atom Brain Phase 2).
 */
class AtomMultiTurnMemoryEngineTest extends TestCase
{
    private string $tempStorage;
    private MultiTurnContextMemoryEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempStorage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_memory_' . uniqid() . '.json';
        $this->engine = new MultiTurnContextMemoryEngine(new SecretRedactor(), $this->tempStorage);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempStorage)) {
            @unlink($this->tempStorage);
        }
        parent::tearDown();
    }

    public function testRecordTurnAndWorkingMemoryWindow(): void
    {
        $turn1 = $this->engine->recordTurn('Hello Atom', 'Hello! How can I help you today?');
        $this->assertNotEmpty($turn1['id']);
        $this->assertEquals('Hello Atom', $turn1['user']);

        $status = $this->engine->getMemoryStatus();
        $this->assertEquals(1, $status['working_memory_count']);

        // Insert 35 turns to test max 30 window limit
        for ($i = 0; $i < 35; $i++) {
            $this->engine->recordTurn("Message {$i}", "Reply {$i}");
        }

        $statusAfter = $this->engine->getMemoryStatus();
        $this->assertLessThanOrEqual(30, $statusAfter['working_memory_count']);
    }

    public function testStoreAndRetrieveFacts(): void
    {
        $res = $this->engine->storeFact('preference', 'Always use CodeIgniter 4 standards', 0.95);
        $this->assertTrue($res['success']);

        $facts = $this->engine->retrieveRelevantFacts('CodeIgniter');
        $this->assertNotEmpty($facts);
        $this->assertEquals('preference', $facts[0]['category']);
        $this->assertStringContainsString('CodeIgniter 4', $facts[0]['fact']);
    }

    public function testForgetFact(): void
    {
        $res = $this->engine->storeFact('tech', 'We run on MySQL 8.0 cluster');
        $id = $res['stored']['id'];

        $forgot = $this->engine->forgetFact($id);
        $this->assertTrue($forgot);

        $status = $this->engine->getMemoryStatus();
        $this->assertCount(0, $status['facts']);
    }

    public function testAutoExtractFactsFromUserInput(): void
    {
        $this->engine->recordTurn('Always remember that I prefer short bullet point answers', 'Understood.');
        
        $status = $this->engine->getMemoryStatus();
        $this->assertGreaterThanOrEqual(1, $status['facts_count']);
        $this->assertStringContainsString('short bullet point', $status['facts'][0]['fact']);
    }

    public function testAnaphoraResolution(): void
    {
        // Turn 1 mentions User.php and AuthController.php
        $this->engine->recordTurn('Let us inspect User.php and AuthController.php for bugs', 'Examining both files.');

        // Turn 2 uses pronoun "the first one"
        $resolved = $this->engine->resolveAnaphora('Can you optimize the first one?');
        $this->assertTrue($resolved['has_anaphora']);
        $this->assertTrue($resolved['resolved']);
        $this->assertEquals('User.php', $resolved['contextual_target']);

        // Turn 3 uses pronoun "the second one"
        $resolved2 = $this->engine->resolveAnaphora('What about the second one?');
        $this->assertTrue($resolved2['has_anaphora']);
        $this->assertTrue($resolved2['resolved']);
        $this->assertEquals('AuthController.php', $resolved2['contextual_target']);
    }

    public function testSentimentVelocityAndAdaptiveTone(): void
    {
        // Start with frustrated sentiment
        $this->engine->recordTurn('This is terrible, the server has an error and is broken', 'Let me help you fix that.');
        
        // Progress into happy/relieved
        $this->engine->recordTurn('It is working now, thank you so much, awesome!', 'You are very welcome!');

        $velocity = $this->engine->calculateSentimentVelocity();
        $this->assertIsArray($velocity);
        $this->assertGreaterThan(0, $velocity['velocity']);
        $this->assertEquals('improving', $velocity['trend']);
        $this->assertEquals('collaborative_enthusiastic', $velocity['recommended_tone']);
    }

    public function testContextualPromptInjection(): void
    {
        $this->engine->storeFact('rule', 'Never expose database credentials in logs');
        $injection = $this->engine->getContextualPromptInjection('database logs');

        $this->assertStringContainsString('Remembered User Context & Facts', $injection);
        $this->assertStringContainsString('Never expose database credentials', $injection);
        $this->assertStringContainsString('Tone & Emotional Calibration', $injection);
    }
}
