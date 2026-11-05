<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\ContainerSecurityScanner;

class ContainerSecurityScannerTest extends TestCase
{
    public function testScansDockerfileAndReportsRecommendations(): void
    {
        $scanner = new ContainerSecurityScanner();
        $dockerfile = "FROM php:8.3-apache\nUSER root\nRUN apt-get update && apt-get install -y git\n";
        $res = $scanner->scanContainer($dockerfile);

        $this->assertEquals('COMPLETED', $res['scan_status']);
        $this->assertTrue($res['can_deploy']); // No critical CVEs
        $this->assertGreaterThan(0, $res['vulnerabilities_found']);
        $this->assertNotEmpty($res['recommendations']);
    }
}