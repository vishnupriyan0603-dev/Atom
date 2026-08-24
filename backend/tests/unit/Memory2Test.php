<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Atom\Memory\StructuredMemory;
use Atom\Memory\StructuredMemoryService;

final class Memory2Test extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';

    public function testMemorySaveExtractionAndRetrieval(): void
    {
        $service = new StructuredMemoryService();

        // 1. Extraction test
        $extracted = $service->extractMemoryIntent("remember that I prefer dark theme");
        $this->assertEquals('preference', $extracted['type']);
        $this->assertEquals('I prefer dark theme', $extracted['content']);

        // 2. Save memory
        $memory = new StructuredMemory(
            userId: 1,
            type: 'preference',
            content: 'I prefer dark theme',
            importance: 8
        );

        $saved = $service->saveMemory($memory);
        $this->assertNotNull($saved);
        $this->assertGreaterThan(0, $saved->id);

        // 3. Retrieval test with ranking
        $ranked = $service->retrieveRankedMemories(1);
        $this->assertNotEmpty($ranked);
        $this->assertEquals('I prefer dark theme', $ranked[0]->content);
        $this->assertEquals(8, $ranked[0]->importance);
    }

    public function testDeduplicationAndUserIsolation(): void
    {
        $service = new StructuredMemoryService();

        $memUser1 = new StructuredMemory(userId: 10, type: 'fact', content: 'Secret User 10 Data', importance: 9);
        $memUser2 = new StructuredMemory(userId: 20, type: 'fact', content: 'Secret User 20 Data', importance: 9);

        $service->saveMemory($memUser1);
        $service->saveMemory($memUser2);

        // Verify User 10 cannot see User 20 memories
        $user10Memories = $service->retrieveRankedMemories(10);
        $this->assertCount(1, $user10Memories);
        $this->assertEquals('Secret User 10 Data', $user10Memories[0]->content);

        // Deduplication test for User 10
        $dupResult = $service->saveMemory($memUser1);
        $this->assertNotNull($dupResult);
        $user10MemoriesAfter = $service->retrieveRankedMemories(10);
        $this->assertCount(1, $user10MemoriesAfter); // Count remains 1 due to deduplication
    }

    public function testMemoryExpirationFilter(): void
    {
        $service = new StructuredMemoryService();

        $expiredMemory = new StructuredMemory(
            userId: 5,
            type: 'temporary',
            content: 'Temporary Code Verification Token',
            importance: 3,
            expiresAt: date('Y-m-d H:i:s', time() - 3600) // Expired 1 hour ago
        );

        $service->saveMemory($expiredMemory);

        // Should filter out expired memory
        $activeMemories = $service->retrieveRankedMemories(5);
        $this->assertEmpty($activeMemories);
    }
}
