<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Brain\AtomRelationshipEngine;
use Atom\Brain\MultiTurnContextMemoryEngine;
use Atom\Security\SecretRedactor;

/**
 * Security & safety pass tests for AtomRelationshipEngine.
 */
class AtomRelationshipSecurityPassTest extends TestCase
{
    private AtomRelationshipEngine $engine;
    private string $tempProfilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempProfilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sec_rel_profile_' . uniqid() . '.json';
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

    public function testSecretRedactionInRelationshipInputs(): void
    {
        $rawSecret = 'sk-proj-secret1234567890abcdef1234567890';
        $input = "My name is {$rawSecret} and my token is {$rawSecret}";

        $res = $this->engine->processMessage($input);
        $this->assertTrue($res['success']);

        $json = json_encode($res);
        $this->assertStringNotContainsString($rawSecret, $json);
    }

    public function testHighThroughputRelationshipProcessing(): void
    {
        $this->engine->setUserName('Vishnupriyan');
        $this->engine->setActiveTopic('Optimization');

        $start = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->engine->processMessage("all turn {$i}");
        }
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed, '100 relationship processing turns should complete in under 1.0s');
    }

    public function testEmptyAndMalformedMessagesHandledCleanly(): void
    {
        $res = $this->engine->processMessage('   ');
        $this->assertTrue($res['success']);
        $this->assertEquals('standard_message', $res['type']);
    }

    public function testNonStandardUserNameProtection(): void
    {
        // When user says "I am fine" or "I am happy", it should not treat "fine" or "happy" as their name
        $this->engine->processMessage('I am fine today');
        $this->assertNotEquals('Fine', $this->engine->getUserName());

        $this->engine->processMessage('I am testing Atom');
        $this->assertNotEquals('Testing', $this->engine->getUserName());
    }

    public function testProfilePersistenceIntegrity(): void
    {
        $this->engine->setUserName('Vishnupriyan');
        $this->engine->setActiveTopic('Math', 'a+b²');

        // Instantiate new instance from same file
        $redactor = new SecretRedactor();
        $memory = new MultiTurnContextMemoryEngine($redactor);
        $newInstance = new AtomRelationshipEngine($redactor, $memory, $this->tempProfilePath);

        $this->assertEquals('Vishnupriyan', $newInstance->getUserName());
        $this->assertEquals('Math', $newInstance->getActiveTopic());
        $this->assertEquals('a+b²', $newInstance->getActiveSubject());
    }
}
