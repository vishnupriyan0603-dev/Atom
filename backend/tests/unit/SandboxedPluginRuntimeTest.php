<?php

use PHPUnit\Framework\TestCase;
use Atom\Sandbox\SandboxedPluginRuntime;

/**
 * Phase 32 — SandboxedPluginRuntime unit tests (5 tests).
 */
class SandboxedPluginRuntimeTest extends TestCase
{
    private SandboxedPluginRuntime $runtime;

    protected function setUp(): void
    {
        $this->runtime = new SandboxedPluginRuntime();
    }

    public function testExecutePermittedCapability(): void
    {
        $plugin = [
            'id'           => 'db_tool',
            'status'       => 'enabled',
            'permissions'  => ['allow_database'],
            'capabilities' => ['explain_query'],
        ];

        $res = $this->runtime->execute($plugin, 'explain_query', ['query' => 'SELECT 1']);

        $this->assertTrue($res['success']);
        $this->assertSame('completed', $res['status']);
        $this->assertArrayHasKey('plan', $res['result']);
        $this->assertGreaterThanOrEqual(0, $res['duration_ms']);
    }

    public function testSecurityViolationWhenLackingRequiredPermission(): void
    {
        $this->expectException(\RuntimeException::class);

        // Lacks 'allow_database'
        $plugin = [
            'id'           => 'untrusted_tool',
            'status'       => 'enabled',
            'permissions'  => [],
            'capabilities' => ['explain_query'],
        ];

        $this->runtime->execute($plugin, 'explain_query', []);
    }

    public function testExecuteOnDisabledPluginThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);

        $plugin = [
            'id'           => 'dormant_tool',
            'status'       => 'disabled',
            'permissions'  => ['allow_network'],
            'capabilities' => ['upload_vault'],
        ];

        $this->runtime->execute($plugin, 'upload_vault', []);
    }

    public function testExecuteUndeclaredMethodThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $plugin = [
            'id'           => 'limited_tool',
            'status'       => 'enabled',
            'permissions'  => [],
            'capabilities' => ['safe_math'],
        ];

        $this->runtime->execute($plugin, 'unauthorized_method', []);
    }

    public function testResolveRequiredPermissionMapping(): void
    {
        $this->assertSame('allow_database', $this->runtime->resolveRequiredPermission('explain_query'));
        $this->assertSame('allow_filesystem', $this->runtime->resolveRequiredPermission('scan_dependencies'));
        $this->assertSame('allow_network', $this->runtime->resolveRequiredPermission('upload_vault'));
        $this->assertSame('allow_process', $this->runtime->resolveRequiredPermission('inspect_containers'));
        $this->assertNull($this->runtime->resolveRequiredPermission('render_latex'));
    }
}
