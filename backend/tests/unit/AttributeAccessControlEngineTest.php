<?php

use PHPUnit\Framework\TestCase;
use Atom\Auth\AttributeAccessControlEngine;

/**
 * Phase 36 — AttributeAccessControlEngine unit tests (5 tests).
 */
class AttributeAccessControlEngineTest extends TestCase
{
    private AttributeAccessControlEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new AttributeAccessControlEngine();
    }

    public function testPublicResourcePermitsReadToAnySubject(): void
    {
        $subject = ['user_id' => 'usr_1', 'role' => 'MEMBER'];
        $resource = ['id' => 'doc_1', 'classification' => 'PUBLIC'];

        $res = $this->engine->evaluate($subject, 'read', $resource);

        $this->assertTrue($res['allowed']);
        $this->assertStringContainsString('PUBLIC', $res['reason']);
    }

    public function testRestrictedResourceDeniesAccessWithoutMfa(): void
    {
        $subject = ['user_id' => 'usr_2', 'role' => 'ADMIN', 'mfa_enabled' => false];
        $resource = ['id' => 'sec_vault', 'classification' => 'RESTRICTED'];

        $res = $this->engine->evaluate($subject, 'decrypt', $resource);

        $this->assertFalse($res['allowed']);
        $this->assertStringContainsString('MFA', $res['reason']);
    }

    public function testRestrictedResourceAllowsAccessWithMfaAndAdminRole(): void
    {
        $subject = ['user_id' => 'usr_2', 'role' => 'ADMIN', 'mfa_enabled' => true];
        $resource = ['id' => 'sec_vault', 'classification' => 'RESTRICTED'];

        $res = $this->engine->evaluate($subject, 'decrypt', $resource);

        $this->assertTrue($res['allowed']);
    }

    public function testEnvironmentIpWhitelistEnforcement(): void
    {
        $subject = ['user_id' => 'usr_3', 'role' => 'ADMIN', 'mfa_enabled' => true];
        $resource = ['id' => 'db_prod', 'classification' => 'INTERNAL'];
        $env = [
            'ip_address'  => '198.51.100.25',
            'allowed_ips' => ['10.0.0.1', '127.0.0.1'],
        ];

        $res = $this->engine->evaluate($subject, 'read', $resource, $env);

        $this->assertFalse($res['allowed']);
        $this->assertStringContainsString('outside permitted environment whitelist', $res['reason']);
    }

    public function testAnonymousSubjectFailsClosed(): void
    {
        $resource = ['id' => 'doc_internal', 'classification' => 'INTERNAL'];
        $res = $this->engine->evaluate([], 'read', $resource);

        $this->assertFalse($res['allowed']);
        $this->assertStringContainsString('Fail-Closed', $res['reason']);
    }
}
