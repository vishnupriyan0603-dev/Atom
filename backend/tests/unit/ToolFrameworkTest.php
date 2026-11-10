<?php

use PHPUnit\Framework\TestCase;
use Atom\Tools\ToolManager;
use Atom\Tools\ToolRegistry;
use Atom\Tools\ToolDefinition;
use Atom\Tools\ToolInterface;

class DummyLowRiskTool implements ToolInterface
{
    public function getName(): string { return 'dummy_read'; }
    public function execute(array $input): array
    {
        return ['success' => true, 'output' => 'Read ' . ($input['path'] ?? 'file')];
    }
}

class DummyHighRiskTool implements ToolInterface
{
    public function getName(): string { return 'database_drop'; }
    public function execute(array $input): array
    {
        return ['success' => true, 'output' => 'Dropped table ' . ($input['table'] ?? 'all')];
    }
}

class ToolFrameworkTest extends TestCase
{
    public function testToolRegistrationAndSchemaValidation()
    {
        $manager = new ToolManager();
        $tool = new DummyLowRiskTool();

        $def = new ToolDefinition(
            name: 'dummy_read',
            description: 'Reads a file safely',
            inputSchema: ['required' => ['path']],
            permission: 'filesystem.read',
            riskLevel: 'low'
        );

        $manager->registerTool($tool, $def);

        $this->assertTrue($manager->hasTool('dummy_read'));
        $this->assertTrue($def->requiresHumanApproval());

        // Invalid input (missing path)
        $result = $manager->executeTool('dummy_read', []);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing required argument', $result['error']);

        // Every tool, including read-only tools, waits for application approval.
        $validResult = $manager->executeTool('dummy_read', ['path' => 'app.log']);
        $this->assertFalse($validResult['success']);
        $this->assertTrue($validResult['requires_human_gate']);

        $manager->setApprovalProvider(static fn (): bool => true);
        $validResult = $manager->executeTool('dummy_read', ['path' => 'app.log']);
        $this->assertTrue($validResult['success']);
        $this->assertEquals('Read app.log', $validResult['output']);
    }

    public function testHighRiskToolRequiresHumanApproval()
    {
        $manager = new ToolManager();
        $highRiskTool = new DummyHighRiskTool();

        $def = new ToolDefinition(
            name: 'database_drop',
            description: 'Drops database table',
            inputSchema: ['required' => ['table']],
            permission: 'database.write',
            riskLevel: 'high'
        );

        $manager->registerTool($highRiskTool, $def);

        // Without human_approved flag -> must trigger human gate
        $result = $manager->executeTool('database_drop', ['table' => 'users']);
        $this->assertFalse($result['success']);
        $this->assertTrue($result['requires_human_gate']);
        $this->assertEquals('high', $result['risk_level']);

        // Model-supplied approval flags cannot bypass the application-side gate.
        $modelApprovedResult = $manager->executeTool('database_drop', ['table' => 'users', 'human_approved' => true]);
        $this->assertFalse($modelApprovedResult['success']);

        // A trusted application callback represents an actual human approval.
        $manager->setApprovalProvider(static fn (string $name, array $arguments, ToolDefinition $definition): bool => true);
        $approvedResult = $manager->executeTool('database_drop', ['table' => 'users']);
        $this->assertTrue($approvedResult['success']);
        $this->assertEquals('Dropped table users', $approvedResult['output']);
    }

    public function testDisabledToolRejection()
    {
        $manager = new ToolManager();
        $tool = new DummyLowRiskTool();
        $manager->registerTool($tool);

        $manager->getRegistry()->disableTool('dummy_read');

        $result = $manager->executeTool('dummy_read', []);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('disabled', $result['error']);
    }
}
