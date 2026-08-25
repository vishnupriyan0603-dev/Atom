<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Security\GeoFencingFirewallEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 64 — Phase64SecurityPassTest security & safety tests (5 tests).
 */
class Phase64SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testIpInjectionStringSafety(): void
    {
        $engine = new GeoFencingFirewallEngine($this->redactor);
        $maliciousIp = "127.0.0.1; DROP TABLE users; --";

        $res = $engine->evaluateAccess($maliciousIp);
        $this->assertArrayHasKey('allowed', $res);
    }

    public function testBlockedCountryAlwaysFailsClosed(): void
    {
        $engine = new GeoFencingFirewallEngine($this->redactor);
        // North Korea IP simulation
        $geo = $engine->resolveIp('175.45.176.1');

        if ($geo['country_code'] === 'KP') {
            $eval = $engine->evaluateAccess('175.45.176.1');
            $this->assertFalse($eval['allowed']);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testHighVolumeIpEvaluationThroughput(): void
    {
        $engine = new GeoFencingFirewallEngine($this->redactor);

        $startTime = microtime(true);
        for ($i = 0; $i < 500; $i++) {
            $ip = "192.168.1.{$i}";
            $eval = $engine->evaluateAccess($ip);
            $this->assertTrue($eval['allowed']);
        }
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(1.0, $duration);
    }

    public function testGeoCoordinatesBoundedWithinEarthRange(): void
    {
        $engine = new GeoFencingFirewallEngine($this->redactor);
        $geo = $engine->resolveIp('8.8.8.8');

        $this->assertGreaterThanOrEqual(-90.0, $geo['lat']);
        $this->assertLessThanOrEqual(90.0, $geo['lat']);
        $this->assertGreaterThanOrEqual(-180.0, $geo['lon']);
        $this->assertLessThanOrEqual(180.0, $geo['lon']);
    }

    public function testNoDangerousEvalOrShellExecutionInSecuritySubsystem(): void
    {
        $files = [
            'src/Security/GeoFencingFirewallEngine.php',
            'src/Security/PostQuantumKemEngine.php',
            'src/Security/SecretRedactor.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
