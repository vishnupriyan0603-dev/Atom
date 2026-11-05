<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\GitOpsDeploymentEngine;

class GitOpsDeploymentEngineTest extends TestCase
{
    public function testDetectsConfigurationDrift(): void
    {
        $gitops = new GitOpsDeploymentEngine();
        $declared = ['app_version' => '2.1.0', 'replicas' => 3];
        $runtime = ['app_version' => '2.1.0', 'replicas' => 2];

        $drift = $gitops->checkDrift($declared, $runtime);
        $this->assertFalse($drift['is_in_sync']);
        $this->assertEquals(1, $drift['drift_count']);
        $this->assertArrayHasKey('replicas', $drift['drift']);
    }

    public function testExecutesReconciliationSync(): void
    {
        $gitops = new GitOpsDeploymentEngine();
        $res = $gitops->sync();

        $this->assertTrue($res['success']);
        $this->assertEquals('SYNCHRONIZED', $res['status']);
        $this->assertStringStartsWith('GITOPS-', $res['sync_id']);
    }
}