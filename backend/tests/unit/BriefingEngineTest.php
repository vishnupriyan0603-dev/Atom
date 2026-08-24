<?php

use PHPUnit\Framework\TestCase;
use Atom\Daemon\BriefingEngine;

/**
 * Phase 25 — BriefingEngine unit tests (5 tests).
 */
class BriefingEngineTest extends TestCase
{
    private BriefingEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new BriefingEngine();
    }

    public function testMorningBriefingGeneration(): void
    {
        $briefing = $this->engine->generateBriefing('morning');
        $this->assertSame('morning', $briefing['type']);
        $this->assertStringContainsString('Morning Briefing', $briefing['title']);
        $this->assertStringContainsString('Vichu', $briefing['content']);
        $this->assertStringContainsString('IST', $briefing['content']);
    }

    public function testEveningBriefingGeneration(): void
    {
        $briefing = $this->engine->generateBriefing('evening');
        $this->assertSame('evening', $briefing['type']);
        $this->assertStringContainsString('Daily Wrap-up', $briefing['title']);
        $this->assertStringContainsString('Good evening', $briefing['content']);
    }

    public function testBriefingIncludesGateMilestoneAndLearning(): void
    {
        $briefing = $this->engine->generateBriefing('morning');
        $this->assertStringContainsString('GATE 2028', $briefing['content']);
        $this->assertStringContainsString('PHP Full-Stack', $briefing['content']);
    }

    public function testBriefingHealthScoreIntegration(): void
    {
        $briefing = $this->engine->generateBriefing('morning');
        $this->assertArrayHasKey('health_score', $briefing);
        $this->assertGreaterThanOrEqual(0, $briefing['health_score']);
        $this->assertLessThanOrEqual(100, $briefing['health_score']);
    }

    public function testBriefingSummaryTextExists(): void
    {
        $briefing = $this->engine->generateBriefing('morning');
        $this->assertNotEmpty($briefing['summary']);
        $this->assertStringContainsString('System health', $briefing['summary']);
    }
}
