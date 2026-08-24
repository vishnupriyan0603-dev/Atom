<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\PersonalityEngine;

/**
 * Phase 23 — PersonalityEngine unit tests (6 tests).
 */
class BrainPersonalityEngineTest extends TestCase
{
    public function testDefaultStyleIsTechnical(): void
    {
        $engine = new PersonalityEngine();
        $this->assertSame('technical', $engine->getStyle());
    }

    public function testFromOwnerProfileBuildsPersonality(): void
    {
        $profile = [
            'preferred_name' => 'Vichu',
            'full_name'      => 'Vishnupriyan R',
            'communication_preferences' => ['explanation_style' => 'mentor'],
        ];
        $engine = PersonalityEngine::fromOwnerProfile($profile);
        $this->assertSame('mentor', $engine->getStyle());
        $this->assertSame('Vichu', $engine->getOwnerName());
    }

    public function testPersonalityBlockContainsIdentityRule(): void
    {
        $engine = new PersonalityEngine('technical', 'Vichu');
        $block  = $engine->buildPersonalityBlock(['preferred_name' => 'Vichu']);
        $this->assertStringContainsString('NOT human', $block);
        $this->assertStringContainsString('Vichu', $block);
    }

    public function testVoiceModeFlagToggle(): void
    {
        $engine = new PersonalityEngine();
        $this->assertFalse($engine->isVoiceModeActive());
        $engine->setVoiceMode(true);
        $this->assertTrue($engine->isVoiceModeActive());
        $engine->setVoiceMode(false);
        $this->assertFalse($engine->isVoiceModeActive());
    }

    public function testStripMarkdownRemovesFences(): void
    {
        $engine = new PersonalityEngine();
        $md     = "```php\n\$x = 1;\n```\nHello **World**";
        $result = $engine->stripMarkdown($md);
        $this->assertStringNotContainsString('```', $result);
        $this->assertStringNotContainsString('**', $result);
        $this->assertStringContainsString('Hello World', $result);
    }

    public function testApplyPersonalityDoesNotMutateNonGreeting(): void
    {
        $engine   = new PersonalityEngine('technical', 'Vichu');
        $response = "Here is the answer.";
        $result   = $engine->applyPersonality($response, 'coding');
        $this->assertStringContainsString('Here is the answer.', $result);
    }
}
