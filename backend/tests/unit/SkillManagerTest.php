<?php

use PHPUnit\Framework\TestCase;
use Atom\Plugins\SkillManager;
use Atom\Plugins\SkillManifest;
use Atom\Tools\ToolRegistry;
use Atom\Tools\ToolDefinition;
use Atom\Tools\ToolInterface;

class DummySkillTool implements ToolInterface
{
    public function getName(): string { return 'read_file'; }
    public function execute(array $input): array { return ['success' => true]; }
}

class SkillManagerTest extends TestCase
{
    public function testSkillManifestParsingAndRegistration()
    {
        $manager = new SkillManager();
        $this->assertNotEmpty($manager->getSkills());

        $customManifest = new SkillManifest(
            name: 'custom_analyzer',
            version: '2.0.0',
            description: 'Custom code static analysis plugin',
            author: 'Developer',
            tools: ['analyze_code'],
            permissions: ['code.read']
        );

        $manager->registerSkill($customManifest);
        $retrieved = $manager->getSkill('custom_analyzer');

        $this->assertNotNull($retrieved);
        $this->assertEquals('2.0.0', $retrieved->version);
        $this->assertTrue($retrieved->enabled);
    }

    public function testSkillEnableDisableStateToggling()
    {
        $registry = new ToolRegistry();
        $tool = new DummySkillTool();
        $registry->registerTool($tool, new ToolDefinition('read_file', 'Read file'));

        $manager = new SkillManager($registry);

        // Disable filesystem skill
        $disabled = $manager->disableSkill('filesystem');
        $this->assertTrue($disabled);
        $this->assertFalse($manager->getSkill('filesystem')->enabled);
        $this->assertFalse($registry->isEnabled('read_file'));

        // Enable filesystem skill
        $enabled = $manager->enableSkill('filesystem');
        $this->assertTrue($enabled);
        $this->assertTrue($manager->getSkill('filesystem')->enabled);
        $this->assertTrue($registry->isEnabled('read_file'));
    }

    public function testExecutionLogging()
    {
        $manager = new SkillManager();
        $manager->logExecution('filesystem', 'read_file', true);
        $manager->logExecution('database', 'db_query', false, 'Access denied');

        $history = $manager->getExecutionHistory();
        $this->assertCount(2, $history);

        $fsHistory = $manager->getExecutionHistory('filesystem');
        $this->assertCount(1, $fsHistory);
        $this->assertEquals('filesystem', $fsHistory[0]['skill']);
    }
}
