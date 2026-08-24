<?php

namespace App\Controllers\Api;

use Atom\Marketplace\PluginMarketplaceRegistry;
use Atom\Sandbox\PluginCapabilityGateway;

/**
 * Sandboxed Plugin Marketplace API Controller — Phase 32
 *
 * Endpoints:
 * - GET  /api/v1/marketplace/plugins   — List marketplace catalog and installed plugins
 * - POST /api/v1/marketplace/install   — Verify cryptographic signature and install plugin
 * - POST /api/v1/marketplace/uninstall — Uninstall plugin and revoke capabilities
 * - POST /api/v1/marketplace/toggle    — Enable/disable installed plugin
 * - POST /api/v1/marketplace/execute   — Execute sandboxed plugin capability
 */
class Marketplace extends BaseApiController
{
    private static ?PluginMarketplaceRegistry $registryInstance = null;
    private static ?PluginCapabilityGateway $gatewayInstance = null;

    private function getRegistry(): PluginMarketplaceRegistry
    {
        if (self::$registryInstance === null) {
            self::$registryInstance = new PluginMarketplaceRegistry();
        }
        return self::$registryInstance;
    }

    private function getGateway(): PluginCapabilityGateway
    {
        if (self::$gatewayInstance === null) {
            self::$gatewayInstance = new PluginCapabilityGateway($this->getRegistry());
        }
        return self::$gatewayInstance;
    }

    /**
     * GET /api/v1/marketplace/plugins
     */
    public function index()
    {
        $category = $this->request->getGet('category');
        $registry = $this->getRegistry();
        $catalog = $registry->getCatalog($category);
        $installed = $registry->listInstalled();

        return $this->respondSuccess([
            'catalog'   => $catalog,
            'installed' => $installed,
            'total'     => count($catalog),
        ], 'Plugin catalog retrieved successfully');
    }

    /**
     * POST /api/v1/marketplace/install
     */
    public function install()
    {
        $json = $this->request->getJSON(true) ?? [];
        $pluginId = $json['id'] ?? '';

        $registry = $this->getRegistry();

        // If ID provided, find from catalog
        if (!empty($pluginId)) {
            $catalog = $registry->getCatalog();
            $manifest = null;
            foreach ($catalog as $p) {
                if ($p['id'] === $pluginId) {
                    $manifest = $p;
                    break;
                }
            }
            if (!$manifest) {
                return $this->respondError("Plugin '{$pluginId}' not found in catalog", 404);
            }
        } else {
            $manifest = $json;
        }

        try {
            $result = $registry->install($manifest);
            return $this->respondSuccess($result, 'Plugin installed and enabled successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/marketplace/uninstall
     */
    public function uninstall()
    {
        $json = $this->request->getJSON(true) ?? [];
        $pluginId = $json['id'] ?? '';

        if (empty($pluginId)) {
            return $this->respondError('Missing plugin ID', 400);
        }

        $registry = $this->getRegistry();
        $success = $registry->uninstall($pluginId);

        if (!$success) {
            return $this->respondError("Plugin '{$pluginId}' is not installed", 404);
        }

        return $this->respondSuccess(['id' => $pluginId, 'uninstalled' => true], "Plugin '{$pluginId}' uninstalled successfully");
    }

    /**
     * POST /api/v1/marketplace/toggle
     */
    public function toggle()
    {
        $json = $this->request->getJSON(true) ?? [];
        $pluginId = $json['id'] ?? '';
        $enabled = isset($json['enabled']) ? (bool)$json['enabled'] : null;

        if (empty($pluginId)) {
            return $this->respondError('Missing plugin ID', 400);
        }

        try {
            $registry = $this->getRegistry();
            $result = $registry->toggle($pluginId, $enabled);
            return $this->respondSuccess($result, 'Plugin status updated successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/marketplace/execute
     */
    public function execute()
    {
        $json = $this->request->getJSON(true) ?? [];
        $method = $json['method'] ?? '';
        $params = $json['params'] ?? [];

        if (empty($method)) {
            return $this->respondError('Missing method parameter', 400);
        }

        try {
            $gateway = $this->getGateway();
            $result = $gateway->dispatch($method, $params);
            return $this->respondSuccess($result, 'Capability executed in sandbox successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 400);
        }
    }
}
