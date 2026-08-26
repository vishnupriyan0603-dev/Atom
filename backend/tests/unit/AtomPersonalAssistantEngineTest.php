<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomPersonalAssistantEngine;
use Atom\Security\SecretRedactor;

/**
 * AtomPersonalAssistantEngine unit tests (6 tests).
 */
class AtomPersonalAssistantEngineTest extends TestCase
{
    private AtomPersonalAssistantEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new AtomPersonalAssistantEngine(new SecretRedactor());
    }

    public function testDetermineResponseDepthLevels(): void
    {
        // Level 1: Quick direct question
        $this->assertSame(1, $this->engine->determineResponseDepth('I see Honda Splendor on road. price?'));

        // Level 2: Explanation
        $this->assertSame(2, $this->engine->determineResponseDepth('Why is PHP faster with opcache?'));

        // Level 3: Detailed calculation / deep explanation
        $this->assertSame(3, $this->engine->determineResponseDepth('Give me full detail and calculate on-road EMI price for Hero Splendor'));
    }

    public function testDetectEmotionalTone(): void
    {
        $this->assertSame('excited', $this->engine->detectEmotion('Finally! My bike problem is fixed yay'));
        $this->assertSame('frustrated', $this->engine->detectEmotion('My query is broken and throwing error'));
        $this->assertSame('confused', $this->engine->detectEmotion('I dont understand how this works'));
        $this->assertSame('joking', $this->engine->detectEmotion('Haha lol that was funny'));
        $this->assertSame('neutral', $this->engine->detectEmotion('What time is the meeting?'));
    }

    public function testDetectEnglishImprovement(): void
    {
        $tip = $this->engine->detectEnglishImprovement('I am see one bike on road');
        $this->assertNotNull($tip);
        $this->assertStringContainsString('Natural English', $tip['tip']);

        $noTip = $this->engine->detectEnglishImprovement('I saw a bike on the road');
        $this->assertNull($noTip);
    }

    public function testTeachConceptUpdatesTopicScore(): void
    {
        $res = $this->engine->teachConcept('PHP & CodeIgniter', 'Always use Query Builder for prepared statements');

        $this->assertTrue($res['success']);
        $this->assertSame('PHP & CodeIgniter', $res['topic']);
        $this->assertGreaterThanOrEqual(40, $res['score']);
    }

    public function testGetLearningGraphReturnsKnowledgeLevels(): void
    {
        $graph = $this->engine->getLearningGraph();

        $this->assertGreaterThanOrEqual(6, $graph['total_topics']);
        $this->assertArrayHasKey('levels_hierarchy', $graph);
        $this->assertCount(7, $graph['levels_hierarchy']);
    }

    public function testGenerateLocalResponseProvidesConversationalOutput(): void
    {
        $res = $this->engine->generateLocalResponse('I see one Honda Splendor on road. price?');

        $this->assertNotEmpty($res['reply']);
        $this->assertSame(1, $res['depth_level']);
        $this->assertNotNull($res['english_tip']);
        $this->assertStringContainsString('₹80k', $res['reply']);
    }
}
