<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\MultiCloudDeployOrchestrator;

class MultiCloudDeployOrchestratorTest extends TestCase
{
    public function testDeploysToLocalTargetSuccessfully(): void
    {
        $orchestrator = new MultiCloudDeployOrchestrator('e:/xampp/htdocs/my work/Atom');
        $res = $orchestrator->deploy('local');

        $this->assertTrue($res['success']);
        $this->assertEquals('ACTIVE', $res['status']);
        $this->assertStringStartsWith('REL-', $res['release_id']);
    }

    public function testRejectsUnsupportedTarget(): void
    {
        $orchestrator = new MultiCloudDeployOrchestrator();
        $res = $orchestrator->deploy('unknown_cloud');

        $this->assertFalse($res['success']);
        $this->assertStringContainsString('Unsupported deployment target', $res['error']);
    }
}