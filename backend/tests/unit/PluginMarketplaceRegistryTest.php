<?php

use PHPUnit\Framework\TestCase;
use Atom\Marketplace\PluginMarketplaceRegistry;

/**
 * Phase 32 — PluginMarketplaceRegistry unit tests (5 tests).
 */
class PluginMarketplaceRegistryTest extends TestCase
{
    private PluginMarketplaceRegistry $registry;

    protected function setUp(): void
    {
        PluginMarketplaceRegistry::reset();
        $this->registry = new PluginMarketplaceRegistry();
    }

    public function testGetCatalogAndFilterByCategory(): void
    {
        $all = $this->registry->getCatalog();
        $this->assertGreaterThanOrEqual(5, count($all));

        $dbOnly = $this->registry->getCatalog('database');
        $this->assertNotEmpty($dbOnly);
        foreach ($dbOnly as $p) {
            $this->assertSame('database', $p['category']);
        }
    }

    public function testInstallPluginSuccessfully(): void
    {
        $manifest = [
            'id'           => 'test_plugin',
            'name'         => 'Test Plugin',
            'version'      => '1.0.0',
            'author'       => 'Test Author',
            'permissions'  => ['allow_database'],
            'capabilities' => ['test_method'],
        ];

        $res = $this->registry->install($manifest);

        $this->assertTrue($res['installed']);
        $this->assertSame('enabled', $res['plugin']['status']);

        $installed = $this->registry->getInstalled('test_plugin');
        $this->assertNotNull($installed);
        $this->assertSame('Test Plugin', $installed['name']);
    }

    public function testUninstallPlugin(): void
    {
        $manifest = [
            'id'           => 'removable_plugin',
            'name'         => 'Removable',
            'version'      => '1.0.0',
            'author'       => 'Dev',
            'permissions'  => [],
            'capabilities' => ['ping'],
        ];

        $this->registry->install($manifest);
        $this->assertNotNull($this->registry->getInstalled('removable_plugin'));

        $uninstalled = $this->registry->uninstall('removable_plugin');
        $this->assertTrue($uninstalled);
        $this->assertNull($this->registry->getInstalled('removable_plugin'));
    }

    public function testTogglePluginStatus(): void
    {
        $manifest = [
            'id'           => 'toggleable_plugin',
            'name'         => 'Toggleable',
            'version'      => '1.0.0',
            'author'       => 'Dev',
            'permissions'  => [],
            'capabilities' => ['ping'],
        ];

        $this->registry->install($manifest);

        // Toggle to disabled
        $res = $this->registry->toggle('toggleable_plugin', false);
        $this->assertFalse($res['enabled']);
        $this->assertSame('disabled', $res['status']);

        // Toggle to enabled
        $res2 = $this->registry->toggle('toggleable_plugin', true);
        $this->assertTrue($res2['enabled']);
        $this->assertSame('enabled', $res2['status']);
    }

    public function testToggleNonExistentPluginThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->registry->toggle('non_existent_plugin');
    }
}
