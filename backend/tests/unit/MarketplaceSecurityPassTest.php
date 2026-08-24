<?php

use PHPUnit\Framework\TestCase;
use Atom\Marketplace\PluginPackageSigner;
use Atom\Marketplace\PluginMarketplaceRegistry;
use Atom\Sandbox\SandboxedPluginRuntime;

/**
 * Phase 32 — MarketplaceSecurityPassTest security & safety tests (5 tests).
 */
class MarketplaceSecurityPassTest extends TestCase
{
    public function testSecretRedactionInPluginMetadataAndOutputs(): void
    {
        $registry = new PluginMarketplaceRegistry();
        $manifest = [
            'id'           => 'secret_leak_plugin',
            'name'         => 'Plugin with sk-ant-api03-secret12345678901234567890',
            'version'      => '1.0.0',
            'author'       => 'Author with gh_secret_token_1234567890abcdef1234',
            'permissions'  => [],
            'capabilities' => ['format_equation'],
        ];

        $res = $registry->install($manifest);

        $this->assertStringNotContainsString('sk-ant-api03', $res['plugin']['name']);
        $this->assertStringContainsString('[REDACTED', $res['plugin']['name']);
    }

    public function testPathTraversalAttackRejectionInPluginId(): void
    {
        $signer = new PluginPackageSigner();
        $traversalManifest = [
            'id'           => '../../../etc/passwd',
            'name'         => 'Exploit Plugin',
            'version'      => '1.0.0',
            'author'       => 'Attacker',
            'permissions'  => [],
            'capabilities' => ['ping'],
        ];

        $validation = $signer->validateManifestSchema($traversalManifest);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('invalid characters', $validation['errors'][0]);
    }

    public function testTamperedSignatureRejectionOnInstall(): void
    {
        $this->expectException(\RuntimeException::class);

        $registry = new PluginMarketplaceRegistry();
        $manifest = [
            'id'           => 'tampered_pkg',
            'name'         => 'Tampered Package',
            'version'      => '1.0.0',
            'author'       => 'Dev',
            'permissions'  => [],
            'capabilities' => ['ping'],
            'signature'    => 'invalid_forged_hmac_signature_999',
        ];

        $registry->install($manifest);
    }

    public function testPermissionEscalationBlockedInSandbox(): void
    {
        $this->expectException(\RuntimeException::class);

        $runtime = new SandboxedPluginRuntime();
        // Attacker declares unpermitted capability call
        $plugin = [
            'id'           => 'low_priv_plugin',
            'status'       => 'enabled',
            'permissions'  => [], // NO permissions granted
            'capabilities' => ['upload_vault'], // Requires 'allow_network'
        ];

        $runtime->execute($plugin, 'upload_vault');
    }

    public function testNoEvalOrShellExecutionInMarketplaceSubsystem(): void
    {
        $rootDir = dirname(__DIR__, 3);
        $signerCode = file_get_contents($rootDir . '/src/Marketplace/PluginPackageSigner.php');
        $registryCode = file_get_contents($rootDir . '/src/Marketplace/PluginMarketplaceRegistry.php');
        $runtimeCode = file_get_contents($rootDir . '/src/Sandbox/SandboxedPluginRuntime.php');

        $this->assertNotFalse($signerCode);
        $this->assertNotFalse($registryCode);
        $this->assertNotFalse($runtimeCode);

        $this->assertStringNotContainsString('eval(', $signerCode);
        $this->assertStringNotContainsString('eval(', $registryCode);
        $this->assertStringNotContainsString('eval(', $runtimeCode);
        $this->assertStringNotContainsString('exec(', $runtimeCode);
        $this->assertStringNotContainsString('shell_exec(', $runtimeCode);
    }
}
