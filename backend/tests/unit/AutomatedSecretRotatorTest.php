<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\AutomatedSecretRotator;

class AutomatedSecretRotatorTest extends TestCase
{
    public function testRegistersAndRotatesSecret(): void
    {
        $rotator = new AutomatedSecretRotator();
        $rotator->setSecret('JWT_SECRET', 'initial_secret_123');

        $res = $rotator->rotateSecret('JWT_SECRET');
        $this->assertTrue($res['success']);
        $this->assertEquals(2, $res['version']);

        $status = $rotator->getVaultStatus();
        $this->assertEquals(1, $status['secrets_count']);
        $this->assertEquals('ACTIVE', $status['vault_entries']['JWT_SECRET']['status']);
    }
}