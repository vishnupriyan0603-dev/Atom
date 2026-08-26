<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomRelationshipEngine;
use Atom\Brain\MultiTurnContextMemoryEngine;
use Atom\Security\SecretRedactor;

/**
 * Unit test suite for AtomRelationshipEngine (ATOM RELATIONSHIP ENGINE).
 */
class AtomRelationshipEngineTest extends TestCase
{
    private AtomRelationshipEngine $engine;
    private string $tempProfilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempProfilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_rel_profile_' . uniqid() . '.json';
        $redactor = new SecretRedactor();
        $memory = new MultiTurnContextMemoryEngine($redactor);
        $this->engine = new AtomRelationshipEngine($redactor, $memory, $this->tempProfilePath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempProfilePath)) {
            @unlink($this->tempProfilePath);
        }
        parent::tearDown();
    }

    public function testUserIdentityStorageAndRecall(): void
    {
        // 1. User introduces themselves
        $resIntro = $this->engine->processMessage('Hi, I am Vishnupriyan.');
        $this->assertTrue($resIntro['success']);
        $this->assertEquals('Vishnupriyan', $this->engine->getUserName());

        // 2. User later asks for their name
        $resQuery = $this->engine->processMessage('What is my name?');
        $this->assertTrue($resQuery['success']);
        $this->assertEquals('identity_response', $resQuery['type']);
        $this->assertEquals('Your name is Vishnupriyan.', $resQuery['reply']);
    }

    public function testUnknownUserIdentityQuery(): void
    {
        $resQuery = $this->engine->processMessage('What is my name?');
        $this->assertTrue($resQuery['success']);
        $this->assertStringContainsString("I don't think you've told me your name yet", $resQuery['reply']);
    }

    public function testTopicContinuityWithShortFollowUpAll(): void
    {
        // 1. User introduces a math problem
        $this->engine->processMessage('I have a math problem a+b².');
        $this->assertEquals('a+b²', $this->engine->getActiveTopic());

        // 2. User sends short follow-up "all"
        $resAll = $this->engine->processMessage('all');
        $this->assertTrue($resAll['success']);
        $this->assertEquals('short_followup_resolved', $resAll['type']);
        $this->assertStringContainsString('a+b²', $resAll['inferred_meaning']);
        $this->assertStringContainsString('basics', $resAll['reply']);
        $this->assertStringContainsString('a+b²', $resAll['reply']);
    }

    public function testBikeInquiryReferenceResolution(): void
    {
        // 1. User mentions bike
        $this->engine->processMessage('I saw a Honda Splendor today.');
        $this->assertStringContainsString('Splendor', $this->engine->getActiveSubject());

        // 2. User asks "How much?"
        $resHowMuch = $this->engine->processMessage('How much?');
        $this->assertTrue($resHowMuch['success']);
        $this->assertStringContainsString('₹80k–₹95k', $resHowMuch['reply']);
    }

    public function testUserCorrectionUpdatesActiveSubject(): void
    {
        $this->engine->processMessage('I am looking at a Splendor.');

        // User corrects Atom
        $resCorrection = $this->engine->processMessage('No, I mean the 2025 Splendor.');
        $this->assertTrue($resCorrection['success']);
        $this->assertEquals('correction_applied', $resCorrection['type']);
        $this->assertEquals('2025 Splendor', $this->engine->getActiveSubject());
        $this->assertStringContainsString('2025 Splendor', $resCorrection['reply']);
    }

    public function testTopicSwitchingDoesNotForceStaleContext(): void
    {
        $this->engine->processMessage('How much is a Splendor?');
        $this->assertEquals('Splendor', $this->engine->getActiveSubject());

        // Topic switch
        $resSwitch = $this->engine->processMessage('Anyway, what is PHP?');
        $this->assertTrue($resSwitch['success']);
        $this->assertEquals('topic_switched', $resSwitch['type']);
        $this->assertEquals('PHP', $this->engine->getActiveSubject());
    }

    public function testRelationshipProfilePromptGeneration(): void
    {
        $this->engine->setUserName('Vishnupriyan');
        $this->engine->setActiveTopic('MySQL Sharding', 'Vitess');

        $prompt = $this->engine->buildRelationshipContextPrompt();
        $this->assertStringContainsString('Vishnupriyan', $prompt);
        $this->assertStringContainsString('Vitess', $prompt);
        $this->assertStringContainsString('Topic Continuity Rule', $prompt);
        $this->assertStringContainsString('Context Priority', $prompt);
    }

    public function testVariousShortFollowUpsHandledGracefully(): void
    {
        $this->engine->setActiveTopic('Database Migration', 'MySQL Partitioning');

        $cases = ['why', 'how', 'calculate', 'explain', 'then?'];
        foreach ($cases as $case) {
            $res = $this->engine->processMessage($case);
            $this->assertTrue($res['success']);
            $this->assertEquals('short_followup_resolved', $res['type']);
            $this->assertNotEmpty($res['reply']);
        }
    }
}
