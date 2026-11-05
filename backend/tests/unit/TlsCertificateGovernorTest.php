<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\TlsCertificateGovernor;

class TlsCertificateGovernorTest extends TestCase
{
    public function testInspectsLocalCertificate(): void
    {
        $tls = new TlsCertificateGovernor();
        $res = $tls->inspectCertificate('localhost');

        $this->assertEquals('VALID', $res['status']);
        $this->assertTrue($res['is_self_signed']);
        $this->assertEquals(90, $res['days_remaining']);
        $this->assertStringContainsString('TLS 1.3', $res['cipher_suite']);
    }

    public function testGeneratesAcmeChallenge(): void
    {
        $tls = new TlsCertificateGovernor();
        $res = $tls->generateAcmeChallenge('api.atom.local');

        $this->assertEquals('api.atom.local', $res['domain']);
        $this->assertEquals('http-01', $res['challenge_type']);
        $this->assertStringStartsWith('/.well-known/acme-challenge/', $res['challenge_path']);
    }
}