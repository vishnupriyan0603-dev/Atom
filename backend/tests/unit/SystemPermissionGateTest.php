<?php

use PHPUnit\Framework\TestCase;
use Atom\Security\SystemPermissionGate;
use Atom\Arc\ArcMasterController;

require_once dirname(__DIR__, 2) . '/../src/Security/SystemPermissionGate.php';
require_once dirname(__DIR__, 2) . '/../src/Arc/ArcMasterController.php';

/**
 * Unit tests for SystemPermissionGate (Phase 110: Full System Access & Permission Gate).
 */
class SystemPermissionGateTest extends TestCase
{
    private SystemPermissionGate $gate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gate = new SystemPermissionGate();
    }

    public function testEvaluatesRiskLevelsAccurately(): void
    {
        $this->assertSame(SystemPermissionGate::LEVEL_0_READ_ONLY, $this->gate->getRiskLevel('ports'));
        $this->assertSame(SystemPermissionGate::LEVEL_0_READ_ONLY, $this->gate->getRiskLevel('telemetry'));
        $this->assertSame(SystemPermissionGate::LEVEL_1_SAFE_INTERACTION, $this->gate->getRiskLevel('open_url'));
        $this->assertSame(SystemPermissionGate::LEVEL_1_SAFE_INTERACTION, $this->gate->getRiskLevel('open_application'));
        $this->assertSame(SystemPermissionGate::LEVEL_2_SENSITIVE_WRITE, $this->gate->getRiskLevel('write_system_file'));
        $this->assertSame(SystemPermissionGate::LEVEL_3_CRITICAL_SYSTEM, $this->gate->getRiskLevel('kill_process'));
        $this->assertSame(SystemPermissionGate::LEVEL_3_CRITICAL_SYSTEM, $this->gate->getRiskLevel('restart_service'));
    }

    public function testEverySystemActionRequiresPermission(): void
    {
        $this->assertTrue($this->gate->requiresPermission('ports'));
        $this->assertTrue($this->gate->requiresPermission('open_url'));
        $this->assertTrue($this->gate->requiresPermission('kill_process'));
        $this->assertTrue($this->gate->requiresPermission('restart_service'));
        $this->assertTrue($this->gate->requiresPermission('write_system_file'));
    }

    public function testCreateAndResolvePermissionRequest(): void
    {
        $req = $this->gate->createPermissionRequest('kill_process', 'Terminate hung process', 'notepad.exe');
        $this->assertIsArray($req);
        $this->assertArrayHasKey('request_id', $req);
        $this->assertSame('PENDING', $req['status']);

        $res = $this->gate->resolvePermission($req['request_id'], 'YES');
        $this->assertTrue($res['success']);
        $this->assertTrue($res['is_granted']);
        $this->assertSame('GRANTED', $res['request']['status']);
    }

    public function testRejectPermissionRequest(): void
    {
        $req = $this->gate->createPermissionRequest('restart_service', 'Restart database', 'mysql');
        $res = $this->gate->resolvePermission($req['request_id'], 'NO');

        $this->assertTrue($res['success']);
        $this->assertFalse($res['is_granted']);
        $this->assertSame('REJECTED', $res['request']['status']);
    }

    public function testSessionPermissionCannotBypassIndividualPrompts(): void
    {
        $this->assertTrue($this->gate->requiresPermission('kill_process'));

        $this->gate->grantSessionPermission('all');
        $this->assertTrue($this->gate->requiresPermission('kill_process'));
        $this->assertTrue($this->gate->requiresPermission('restart_service'));
        $this->assertFalse($this->gate->hasSessionPermission('kill_process'));
        $this->assertSame([], $this->gate->getStatus()['active_session_grants']);

        // Revoke
        $this->gate->revokePermissions();
        $this->assertTrue($this->gate->requiresPermission('kill_process'));
    }

    public function testLegacyBypassAndSessionApprovalDecisionsAreDisabled(): void
    {
        $gate = new SystemPermissionGate(true);
        $this->assertTrue($gate->requiresPermission('telemetry'));

        $request = $gate->createPermissionRequest('kill_process', 'Terminate a process', 'worker.exe');
        $result = $gate->resolvePermission($request['request_id'], 'ALLOW_SESSION');
        $this->assertFalse($result['is_granted']);
        $this->assertSame('REJECTED', $result['request']['status']);
        $this->assertFalse($gate->hasSessionPermission('kill_process'));
    }

    public function testMasterControllerWiresPermissionGate(): void
    {
        $master = new ArcMasterController();
        $gate = $master->getPermissionGate();

        $this->assertInstanceOf(SystemPermissionGate::class, $gate);
        $status = $gate->getStatus();
        $this->assertArrayHasKey('gate', $status);
        $this->assertStringContainsString('Phase 110', $status['gate']);
    }
}

