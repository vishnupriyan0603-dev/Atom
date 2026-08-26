<?php

namespace Atom\Marketplace;

use Atom\Security\SecretRedactor;

/**
 * Plugin Marketplace Registry — Phase 32
 *
 * Curated catalog of enterprise plugins, handling search, installation,
 * uninstallation, and runtime capability enablement.
 */
class PluginMarketplaceRegistry
{
    private static array $installedPlugins = [];
    private PluginPackageSigner $signer;
    private SecretRedactor $redactor;

    public function __construct(?PluginPackageSigner $signer = null, ?SecretRedactor $redactor = null)
    {
        $this->signer = $signer ?? new PluginPackageSigner();
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Retrieves the curated list of marketplace plugins.
     */
    public function getCatalog(?string $category = null): array
    {
        $catalog = [
            [
                'id'               => 'db_query_optimizer',
                'name'             => 'Database Query Optimizer & Index Advisor',
                'version'          => '1.2.0',
                'author'           => 'ATOM Core Engineering',
                'category'         => 'database',
                'description'      => 'Analyzes slow SQL queries, recommends composite indexes, and optimizes execution plans.',
                'rating'           => 4.9,
                'downloads'        => 3420,
                'verified'         => true,
                'is_free'          => true,
                'permissions'      => ['allow_database'],
                'capabilities'     => ['explain_query', 'suggest_indexes', 'bench_query'],
                'min_core_version' => '1.0.0',
            ],
            [
                'id'               => 'sec_vulnerability_scanner',
                'name'             => 'Security Dependency & CVE Vulnerability Scanner',
                'version'          => '2.0.1',
                'author'           => 'CyberTrust Security Labs',
                'category'         => 'security',
                'description'      => 'Automated CVE scanning for Composer, npm, and NuGet dependency packages.',
                'rating'           => 5.0,
                'downloads'        => 8920,
                'verified'         => true,
                'is_free'          => true,
                'permissions'      => ['allow_filesystem', 'allow_network'],
                'capabilities'     => ['scan_dependencies', 'audit_cve', 'generate_sbom'],
                'min_core_version' => '1.0.0',
            ],
            [
                'id'               => 'google_search_planner',
                'name'             => 'Google Search & Live Web Information Harvester',
                'version'          => '1.0.0',
                'author'           => 'ATOM Autonomous Research',
                'category'         => 'free',
                'description'      => 'Dispatches Google searches, harvests live internet sources, and grounds goal plans with verified references.',
                'rating'           => 5.0,
                'downloads'        => 12400,
                'verified'         => true,
                'is_free'          => true,
                'permissions'      => ['allow_network'],
                'capabilities'     => ['google_search', 'harvest_web_facts', 'synthesize_research'],
                'min_core_version' => '1.0.0',
            ],
            [
                'id'               => 'github_repo_syncer',
                'name'             => 'GitHub Repository & Release Automation Syncer',
                'version'          => '1.1.2',
                'author'           => 'OpenSource DevOps Mesh',
                'category'         => 'free',
                'description'      => 'Syncs GitHub issues, automates PR validation hooks, and drafts changelogs.',
                'rating'           => 4.8,
                'downloads'        => 7310,
                'verified'         => true,
                'is_free'          => true,
                'permissions'      => ['allow_network', 'allow_filesystem'],
                'capabilities'     => ['sync_repo', 'draft_release', 'track_issues'],
                'min_core_version' => '1.0.0',
            ],
            [
                'id'               => 'system_resource_watcher',
                'name'             => 'System Hardware & Core Performance Watchdog',
                'version'          => '1.0.3',
                'author'           => 'ATOM Core Labs',
                'category'         => 'free',
                'description'      => 'Monitors CPU, RAM, Disk I/O, and Apache/PHP socket pools in real time.',
                'rating'           => 4.9,
                'downloads'        => 9520,
                'verified'         => true,
                'is_free'          => true,
                'permissions'      => ['allow_process'],
                'capabilities'     => ['inspect_resources', 'check_socket_health', 'alert_thresholds'],
                'min_core_version' => '1.0.0',
            ],
            [
                'id'               => 'dev_doc_generator',
                'name'             => 'Developer API Documentation & OpenAPI 3.0 Synthesizer',
                'version'          => '1.0.1',
                'author'           => 'API Engineering Group',
                'category'         => 'free',
                'description'      => 'Generates clean Markdown & interactive Swagger documentation from PHP docblocks.',
                'rating'           => 4.8,
                'downloads'        => 6430,
                'verified'         => true,
                'is_free'          => true,
                'permissions'      => ['allow_filesystem'],
                'capabilities'     => ['generate_openapi', 'build_markdown_docs', 'lint_api_spec'],
                'min_core_version' => '1.0.0',
            ],
            [
                'id'               => 'aws_s3_vault_exporter',
                'name'             => 'AWS S3 Zero-Knowledge Encrypted Vault Exporter',
                'version'          => '1.0.4',
                'author'           => 'CloudSync Foundation',
                'category'         => 'cloud',
                'description'      => 'Exports encrypted SQLite & Knowledge Graph backups to AWS S3 / Cloudflare R2.',
                'rating'           => 4.8,
                'downloads'        => 1850,
                'verified'         => true,
                'is_free'          => false,
                'permissions'      => ['allow_network', 'allow_filesystem'],
                'capabilities'     => ['upload_vault', 'download_vault', 'list_snapshots'],
                'min_core_version' => '1.0.0',
            ],
            [
                'id'               => 'latex_formula_renderer',
                'name'             => 'LaTeX Mathematical Formula & Diagram Synthesizer',
                'version'          => '1.1.0',
                'author'           => 'MathScience AI',
                'category'         => 'math',
                'description'      => 'Renders symbolic equations into SVG/PNG LaTeX math representations and diagrams.',
                'rating'           => 4.7,
                'downloads'        => 2110,
                'verified'         => true,
                'is_free'          => true,
                'permissions'      => [],
                'capabilities'     => ['render_latex', 'synthesize_svg', 'format_equation'],
                'min_core_version' => '1.0.0',
            ],
            [
                'id'               => 'docker_container_orchestrator',
                'name'             => 'Docker Container Lifecycle & Health Monitor',
                'version'          => '1.3.2',
                'author'           => 'DevOps Automation Tools',
                'category'         => 'devops',
                'description'      => 'Inspects container health, manages service restarts, and analyzes container logs.',
                'rating'           => 4.9,
                'downloads'        => 5640,
                'verified'         => true,
                'is_free'          => false,
                'permissions'      => ['allow_process', 'allow_network'],
                'capabilities'     => ['inspect_containers', 'restart_service', 'tail_logs'],
                'min_core_version' => '1.0.0',
            ],
        ];

        // Attach installation state
        foreach ($catalog as &$plugin) {
            $isInstalled = isset(self::$installedPlugins[$plugin['id']]);
            $plugin['is_installed'] = $isInstalled;
            $plugin['is_enabled'] = $isInstalled && (self::$installedPlugins[$plugin['id']]['status'] === 'enabled');
            $plugin['signature'] = $this->signer->signManifest($plugin);
        }

        if ($category !== null && $category !== '' && $category !== 'all') {
            $catalog = array_values(array_filter($catalog, function ($p) use ($category) {
                return strtolower($p['category']) === strtolower($category);
            }));
        }

        return $catalog;
    }

    /**
     * Installs a plugin package after verifying its manifest schema and signature.
     */
    public function install(array $pluginManifest): array
    {
        $schemaCheck = $this->signer->validateManifestSchema($pluginManifest);
        if (!$schemaCheck['valid']) {
            throw new \InvalidArgumentException('Plugin manifest validation failed: ' . implode('; ', $schemaCheck['errors']));
        }

        $pluginId = $pluginManifest['id'];
        $signature = $pluginManifest['signature'] ?? $this->signer->signManifest($pluginManifest);

        if (!$this->signer->verifySignature($pluginManifest, $signature)) {
            throw new \RuntimeException("Invalid or tampered cryptographic signature for plugin '{$pluginId}'");
        }

        $installedRecord = [
            'id'           => $pluginId,
            'name'         => $this->redactor->redact($pluginManifest['name']),
            'version'      => $pluginManifest['version'],
            'author'       => $this->redactor->redact($pluginManifest['author']),
            'permissions'  => $pluginManifest['permissions'] ?? [],
            'capabilities' => $pluginManifest['capabilities'] ?? [],
            'status'       => 'enabled',
            'signature'    => $signature,
            'installed_at' => date('c'),
        ];

        self::$installedPlugins[$pluginId] = $installedRecord;

        return [
            'installed' => true,
            'plugin'    => $installedRecord,
            'message'   => "Plugin '{$pluginId}' installed and enabled successfully",
        ];
    }

    /**
     * Uninstalls a plugin from the registry.
     */
    public function uninstall(string $pluginId): bool
    {
        if (isset(self::$installedPlugins[$pluginId])) {
            unset(self::$installedPlugins[$pluginId]);
            return true;
        }
        return false;
    }

    /**
     * Batch installs all free verified plugins.
     */
    public function installAllFree(): array
    {
        $catalog = $this->getCatalog();
        $installed = [];

        foreach ($catalog as $plugin) {
            if (!empty($plugin['is_free']) || $plugin['category'] === 'free') {
                $res = $this->install($plugin);
                $installed[] = $res['plugin'];
            }
        }

        return [
            'success' => true,
            'installed_count' => count($installed),
            'plugins' => $installed,
            'message' => "Successfully installed " . count($installed) . " free plugins!",
        ];
    }

    /**
     * Toggles plugin enabled / disabled status.
     */
    public function toggle(string $pluginId, ?bool $enabled = null): array
    {
        if (!isset(self::$installedPlugins[$pluginId])) {
            throw new \InvalidArgumentException("Plugin '{$pluginId}' is not installed");
        }

        $currentStatus = self::$installedPlugins[$pluginId]['status'];
        $newStatus = ($enabled !== null) ? ($enabled ? 'enabled' : 'disabled') : ($currentStatus === 'enabled' ? 'disabled' : 'enabled');

        self::$installedPlugins[$pluginId]['status'] = $newStatus;

        return [
            'success' => true,
            'id'      => $pluginId,
            'status'  => $newStatus,
            'enabled' => ($newStatus === 'enabled'),
        ];
    }

    /**
     * Retrieves an installed plugin by ID.
     */
    public function getInstalled(string $pluginId): ?array
    {
        return self::$installedPlugins[$pluginId] ?? null;
    }

    /**
     * Lists all currently installed plugins.
     */
    public function listInstalled(): array
    {
        return array_values(self::$installedPlugins);
    }

    /**
     * Clears all installed plugins (for test isolation).
     */
    public static function reset(): void
    {
        self::$installedPlugins = [];
    }
}
