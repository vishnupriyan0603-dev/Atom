<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Marketplace\PluginMarketplaceRegistry;
use Atom\Marketplace\PluginPackageSigner;
use Atom\Security\SecretRedactor;

/**
 * Unit test suite for PluginMarketplaceRegistry & Free Plugins.
 */
class PluginMarketplaceRegistryTest extends TestCase
{
    private PluginMarketplaceRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new PluginMarketplaceRegistry(new PluginPackageSigner(), new SecretRedactor());
    }

    public function testGetCatalogIncludesFreePlugins(): void
    {
        $catalog = $this->registry->getCatalog();
        $this->assertIsArray($catalog);
        $this->assertNotEmpty($catalog);

        $freePlugins = array_filter($catalog, fn($p) => !empty($p['is_free']));
        $this->assertGreaterThanOrEqual(5, count($freePlugins));

        $googlePlugin = null;
        foreach ($catalog as $p) {
            if ($p['id'] === 'google_search_planner') {
                $googlePlugin = $p;
                break;
            }
        }

        $this->assertNotNull($googlePlugin);
        $this->assertTrue($googlePlugin['is_free']);
        $this->assertTrue($googlePlugin['verified']);
        $this->assertContains('google_search', $googlePlugin['capabilities']);
    }

    public function testFilterCategoryFree(): void
    {
        $freeCatalog = $this->registry->getCatalog('free');
        $this->assertIsArray($freeCatalog);
        $this->assertNotEmpty($freeCatalog);

        foreach ($freeCatalog as $plugin) {
            $this->assertEquals('free', $plugin['category']);
            $this->assertTrue($plugin['is_free']);
        }
    }

    public function testInstallAllFreePlugins(): void
    {
        $result = $this->registry->installAllFree();
        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(5, $result['installed_count']);
        $this->assertCount($result['installed_count'], $result['plugins']);

        // Verify that catalog now shows them as installed
        $catalog = $this->registry->getCatalog();
        foreach ($catalog as $plugin) {
            if (!empty($plugin['is_free']) || $plugin['category'] === 'free') {
                $this->assertTrue($plugin['is_installed']);
            }
        }
    }

    public function testInstallAndUninstallSinglePlugin(): void
    {
        $manifest = [
            'id' => 'custom_unit_test_plugin',
            'name' => 'Custom Unit Test Plugin',
            'version' => '1.0.0',
            'author' => 'ATOM Test Suite',
            'category' => 'testing',
            'description' => 'Test plugin for unit testing registry capabilities.',
            'rating' => 5.0,
            'downloads' => 10,
            'verified' => true,
            'permissions' => ['allow_database'],
            'capabilities' => ['run_unit_test'],
            'min_core_version' => '1.0.0',
        ];

        $signer = new PluginPackageSigner();
        $manifest['signature'] = $signer->signManifest($manifest);

        $installRes = $this->registry->install($manifest);
        $this->assertTrue($installRes['installed']);
        $this->assertEquals('custom_unit_test_plugin', $installRes['plugin']['id']);

        // Toggle plugin status
        $toggleRes = $this->registry->toggle('custom_unit_test_plugin', false);
        $this->assertTrue($toggleRes['success']);
        $this->assertEquals('disabled', $toggleRes['status']);

        // Uninstall
        $uninstalled = $this->registry->uninstall('custom_unit_test_plugin');
        $this->assertTrue($uninstalled);
    }
}
