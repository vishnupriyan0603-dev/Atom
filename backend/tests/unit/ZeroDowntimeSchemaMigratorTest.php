<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\ZeroDowntimeSchemaMigrator;

class ZeroDowntimeSchemaMigratorTest extends TestCase
{
    public function testPlansGhostTableMigration(): void
    {
        $migrator = new ZeroDowntimeSchemaMigrator();
        $plan = $migrator->planMigration('users', ['tier' => 'VARCHAR(50) DEFAULT "free"'], ['idx_tier' => ['tier']]);

        $this->assertEquals('users', $plan['table']);
        $this->assertEquals('_users_ghost', $plan['ghost_table']);
        $this->assertTrue($plan['reversible']);
        $this->assertCount(6, $plan['steps']);
    }
}