<?php

namespace Atom\Sandbox;

use Atom\Marketplace\PluginMarketplaceRegistry;

/**
 * Plugin Capability Gateway — Phase 32
 *
 * Hot-reloadable capability router that dispatches AI tool requests
 * to installed and active sandboxed plugins with audit logging.
 */
class PluginCapabilityGateway
{
    private PluginMarketplaceRegistry $registry;
    private SandboxedPluginRuntime $runtime;
    private static array $auditLog = [];

    public function __construct(?PluginMarketplaceRegistry $registry = null, ?SandboxedPluginRuntime $runtime = null)
    {
        $this->registry = $registry ?? new PluginMarketplaceRegistry();
        $this->runtime = $runtime ?? new SandboxedPluginRuntime();
    }

    /**
     * Dispatches a capability method call to the responsible installed plugin.
     *
     * @param string $method Capability method name.
     * @param array $params Arguments.
     * @return array Execution result.
     */
    public function dispatch(string $method, array $params = []): array
    {
        $installed = $this->registry->listInstalled();
        $targetPlugin = null;

        foreach ($installed as $plugin) {
            if ($plugin['status'] === 'enabled' && in_array($method, $plugin['capabilities'] ?? [], true)) {
                $targetPlugin = $plugin;
                break;
            }
        }

        if ($targetPlugin === null) {
            throw new \RuntimeException("No active plugin found capable of executing method '{$method}'");
        }

        $result = $this->runtime->execute($targetPlugin, $method, $params);

        // Record audit event
        self::$auditLog[] = [
            'plugin_id'   => $targetPlugin['id'],
            'method'      => $method,
            'success'     => $result['success'],
            'duration_ms' => $result['duration_ms'] ?? 0,
            'timestamp'   => date('c'),
        ];

        return $result;
    }

    /**
     * Retrieves the list of currently available active capabilities across all plugins.
     */
    public function getActiveCapabilities(): array
    {
        $installed = $this->registry->listInstalled();
        $capabilities = [];

        foreach ($installed as $plugin) {
            if ($plugin['status'] === 'enabled') {
                foreach ($plugin['capabilities'] ?? [] as $cap) {
                    $capabilities[$cap] = [
                        'method'      => $cap,
                        'plugin_id'   => $plugin['id'],
                        'plugin_name' => $plugin['name'],
                        'permissions' => $plugin['permissions'] ?? [],
                    ];
                }
            }
        }

        return $capabilities;
    }

    /**
     * Retrieves recent capability execution audit events.
     */
    public function getAuditLog(int $limit = 20): array
    {
        return array_slice(array_reverse(self::$auditLog), 0, $limit);
    }

    /**
     * Clears audit log (for testing).
     */
    public static function reset(): void
    {
        self::$auditLog = [];
    }
}
