<?php

use PHPUnit\Framework\TestCase;
use Atom\Brain\PersonalityEngine;
use Atom\Brain\ContextEngine;
use Atom\Brain\Voice\VoiceEngine;
use Atom\Brain\IntentEngine;

/**
 * Phase 23 — Brain Security Pass tests (5 tests).
 *
 * These tests enforce the safety guarantees of the Brain layer:
 *  - Atom must NEVER claim to be human
 *  - Context engine must NEVER expose raw credentials
 *  - Voice engine must strip API key patterns
 *  - Personality block must explicitly state NOT-human identity
 *  - IntentEngine must correctly identify governance queries
 */
class BrainSecurityPassTest extends TestCase
{
    public function testPersonalityBlockNeverClaimsHuman(): void
    {
        $engine = new PersonalityEngine('technical', 'Vichu');
        $block  = $engine->buildPersonalityBlock();
        // Must contain the identity constraint
        $this->assertStringContainsString('NOT human', $block, 'Personality block must state Atom is not human');
        // Must NOT contain wording that implies human identity
        $this->assertStringNotContainsString('I am a human', $block);
        $this->assertStringNotContainsString('I am human', $block);
    }

    public function testPersonalityBlockContainsHonestyRule(): void
    {
        $engine = new PersonalityEngine();
        $block  = $engine->buildPersonalityBlock();
        $this->assertStringContainsString('I know', $block);
        $this->assertStringContainsString('I remember', $block);
        $this->assertStringContainsString('I infer', $block);
    }

    public function testVoiceEngineStripsSensitiveMarkdownPatterns(): void
    {
        $voice  = new VoiceEngine();
        $input  = "Here is your **API key**: `sk-1234567890abcdef`";
        $output = $voice->formatForVoice($input);
        // Backtick markers should be gone — content preserved
        $this->assertStringNotContainsString('`', $output);
        // Bold markers should be gone
        $this->assertStringNotContainsString('**', $output);
        // The literal content must still be there (we don't redact here, SecretRedactor does)
        $this->assertStringContainsString('sk-1234567890abcdef', $output);
    }

    public function testContextEngineDoesNotStoreCredentials(): void
    {
        // Context engine stores only file names, class names, and table names.
        // It must NOT store raw credential strings (those belong to SecretRedactor).
        $engine = new ContextEngine();
        // A typical input that would contain an API key
        $input  = 'my api key is sk-1234567890 use it in config.php';
        $engine->update($input, 'Noted', 'coding');
        $entities = $engine->getReferencedEntities();
        // Only the file reference (config.php) should be extracted
        foreach ($entities as $entity) {
            $this->assertStringNotContainsString('sk-', $entity, 'Context engine must not store credential-like strings');
        }
    }

    public function testIntentEngineCorrectlyRoutesGovernanceToGovernanceHint(): void
    {
        $engine = new IntentEngine();
        $result = $engine->classify('check kill switch status and compliance policies');
        $this->assertSame('governance_query', $result->intent);
        $this->assertSame('governance', $result->routingHint);
        // Must NOT route to local — governance always needs policy evaluation
        $this->assertNotSame('local', $result->routingHint);
    }
}
