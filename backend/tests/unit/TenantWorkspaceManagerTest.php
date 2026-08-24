<?php

use PHPUnit\Framework\TestCase;
use Atom\Auth\TenantWorkspaceManager;

/**
 * Phase 36 — TenantWorkspaceManager unit tests (5 tests).
 */
class TenantWorkspaceManagerTest extends TestCase
{
    private TenantWorkspaceManager $manager;

    protected function setUp(): void
    {
        $this->manager = new TenantWorkspaceManager();
    }

    public function testDefaultTenantWorkspaceExists(): void
    {
        $default = $this->manager->getTenant('default');

        $this->assertSame('default', $default['id']);
        $this->assertSame('usr_owner_root', $default['owner_id']);
        $this->assertGreaterThan(0, $default['storage_cap_mb']);
    }

    public function testCreateNewTenantWorkspace(): void
    {
        $tenant = $this->manager->createTenant('acme-corp', 'Acme Corporation', 'usr_acme_admin', [
            'storage_cap_mb' => 20480,
            'compute_units'  => 50000,
        ]);

        $this->assertSame('acme-corp', $tenant['id']);
        $this->assertSame('Acme Corporation', $tenant['name']);
        $this->assertSame(20480, $tenant['storage_cap_mb']);
    }

    public function testDuplicateTenantCreationThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->manager->createTenant('default', 'Duplicate Default', 'usr_other');
    }

    public function testSwitchActiveTenantContext(): void
    {
        $this->manager->createTenant('beta-lab', 'Beta Testing Lab', 'usr_beta');
        $this->manager->setActiveTenant('beta-lab');

        $active = $this->manager->getActiveTenant();
        $this->assertSame('beta-lab', $active['id']);
    }

    public function testAddMemberToTenant(): void
    {
        $this->manager->addMember('default', 'usr_contractor_01', 'AUDITOR');
        $tenant = $this->manager->getTenant('default');

        $this->assertArrayHasKey('usr_contractor_01', $tenant['members']);
        $this->assertSame('AUDITOR', $tenant['members']['usr_contractor_01']);
    }
}
