<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\NaturalDialogueOrchestratorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 69 — NaturalDialogueOrchestratorEngine unit tests (6 tests).
 */
class NaturalDialogueOrchestratorEngineTest extends TestCase
{
    private NaturalDialogueOrchestratorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new NaturalDialogueOrchestratorEngine(new SecretRedactor());
    }

    public function testGreetingHandledNaturallyWithoutCode(): void
    {
        $turn = $this->engine->processTurn('hey, good morning!', ['user_name' => 'Alex']);

        $this->assertTrue($turn['success']);
        $this->assertTrue($turn['is_greeting']);
        $this->assertStringContainsString('Alex', $turn['response']);
        $this->assertStringNotContainsString('```', $turn['response']);
    }

    public function testEmotionalToneDetection(): void
    {
        $this->assertSame('frustrated', $this->engine->detectEmotionalTone('I am so annoyed with this broken bug!'));
        $this->assertSame('confused', $this->engine->detectEmotionalTone('I dont understand how this works, can you explain?'));
        $this->assertSame('playful', $this->engine->detectEmotionalTone('haha that is so funny lol'));
        $this->assertSame('happy', $this->engine->detectEmotionalTone('Awesome, thank you so much, great job!'));
        $this->assertSame('neutral', $this->engine->detectEmotionalTone('What is the capital of France?'));
    }

    public function testGentleEnglishLearningGuidance(): void
    {
        $tip = $this->engine->analyzeEnglishGuidance('I am use two provider in my system.');

        $this->assertNotNull($tip);
        $this->assertSame('am use', $tip['original']);
        $this->assertSame('am using', $tip['suggestion']);
    }

    public function test3TierTeachingExplanationStructure(): void
    {
        $exp = $this->engine->structureTeachingExplanation(
            'Recursion',
            'A function calling itself until a base condition is met.',
            'Russian nesting dolls (Matryoshka).',
            'Always define a clear base condition to avoid infinite loops.'
        );

        $this->assertSame('Recursion', $exp['concept']);
        $this->assertArrayHasKey('tier_1_simple_explanation', $exp);
        $this->assertArrayHasKey('tier_2_concrete_example', $exp);
        $this->assertArrayHasKey('tier_3_practical_advice', $exp);
        $this->assertStringContainsString('Russian nesting dolls', $exp['formatted_markdown']);
    }

    public function testDecisionRecommendationTradeOffs(): void
    {
        $dec = $this->engine->synthesizeDecisionRecommendation(
            'Caching Architecture',
            ['Redis' => 'High speed in-memory', 'MySQL Cache' => 'Simpler, already deployed'],
            'Redis',
            'Higher concurrent throughput and native TTL support.'
        );

        $this->assertSame('Caching Architecture', $dec['topic']);
        $this->assertSame('Redis', $dec['recommended_option']);
    }

    public function testEmptyInputFailsGracefully(): void
    {
        $turn = $this->engine->processTurn('');
        $this->assertFalse($turn['success']);
    }
}
