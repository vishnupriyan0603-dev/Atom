<?php

use PHPUnit\Framework\TestCase;
use Atom\Marketplace\PluginMarketplaceRegistry;
use Atom\Sandbox\SandboxedPluginRuntime;
use Atom\Sandbox\PluginCapabilityGateway;

/**
 * Phase 32 — PluginCapabilityGateway unit tests (5 tests).
 */
class PluginCapabilityGatewayTest extends TestCase
{
    private PluginMarketplaceRegistry $registry;
    private PluginCapabilityGateway $gateway;

    protected function setUp(): void
    {
        PluginMarketplaceRegistry::reset();
        PluginCapabilityGateway::reset();
        $this->registry = new PluginMarketplaceRegistry();
        $this->gateway = new PluginCapabilityGateway($this->registry);
    }

    public function testDispatchToInstalledAndEnabledPlugin(): void
    {
        $manifest = [
            'id'           => 'db_query_optimizer',
            'name'         => 'Database Optimizer',
            'version'      => '1.0.0',
            'author'       => 'ATOM Team',
            'permissions'  => ['allow_database'],
            'capabilities' => ['explain_query', 'suggest_indexes'],
        ];

        $this->registry->install($manifest);

        $res = $this->gateway->dispatch('explain_query', ['query' => 'SELECT * FROM users']);

        $this->assertTrue($res['success']);
        $this->assertSame('db_query_optimizer', $res['plugin_id']);
        $this->assertArrayHasKey('plan', $res['result']);
    }

    public function testDispatchThrowsExceptionWhenNoPluginProvidesCapability(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->gateway->dispatch('non_existent_method');
    }

    public function testDispatchFailsWhenPluginIsDisabled(): void
    {
        $manifest = [
            'id'           => 'cve_scanner',
            'name'         => 'CVE Scanner',
            'version'      => '1.0.0',
            'author'       => 'SecLabs',
            'permissions'  => ['allow_filesystem'],
            'capabilities' => ['scan_dependencies'],
        ];

        $this->registry->install($manifest);
        $this->registry->toggle('cve_scanner', false); // Disable

        $this->expectException(\RuntimeException::class);
        $this->gateway->dispatch('scan_dependencies');
    }

    public function testGetActiveCapabilitiesListsEnabledCapabilities(): void
    {
        $p1 = [
            'id'           => 'math_tool',
            'name'         => 'Math Tool',
            'version'      => '1.0.0',
            'author'       => 'MathSci',
            'permissions'  => [],
            'capabilities' => ['render_latex'],
        ];
        $this->registry->install($p1);

        $caps = $this->gateway->getActiveCapabilities();
        $this->assertArrayHasKey('render_latex', $caps);
        $this->assertSame('math_tool', $caps['render_latex']['plugin_id']);
    }

    public function testAuditLogRecordedOnDispatch(): void
    {
        $manifest = [
            'id'           => 'math_tool',
            'name'         => 'Math Tool',
            'version'      => '1.0.0',
            'author'       => 'MathSci',
            'permissions'  => [],
            'capabilities' => ['render_latex'],
        ];
        $this->registry->install($manifest);

        $this->gateway->dispatch('render_latex');

        $logs = $this->gateway->getAuditLog();
        $this->assertCount(1, $logs);
        $this->assertSame('render_latex', $logs[0]['method']);
        $this->assertSame('math_tool', $logs[0]['plugin_id']);
        $this->assertTrue($logs[0]['success']);
    }
}
