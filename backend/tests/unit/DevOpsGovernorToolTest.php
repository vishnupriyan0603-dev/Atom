<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Tools\DevOpsGovernorTool;

class DevOpsGovernorToolTest extends TestCase
{
    private DevOpsGovernorTool $tool;

    protected function setUp(): void
    {
        $this->tool = new DevOpsGovernorTool();
    }

    public function testToolNameAndDeclaration(): void
    {
        $this->assertEquals('devops_governor', $this->tool->getName());
        $decl = $this->tool->getDeclaration();
        $this->assertArrayHasKey('parameters', $decl);
    }

    public function testExecutesBenchmarkAction(): void
    {
        $res = $this->tool->execute(['action' => 'benchmark', 'iterations' => 10]);
        $this->assertArrayHasKey('latency_ms', $res);
        $this->assertArrayHasKey('memory', $res);
    }

    public function testExecutesMigrationPlanAction(): void
    {
        $res = $this->tool->execute(['action' => 'migrate', 'table' => 'audit_logs']);
        $this->assertEquals('audit_logs', $res['table']);
        $this->assertEquals('_audit_logs_ghost', $res['ghost_table']);
    }
}