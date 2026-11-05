<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\DevOps\DatabaseQueryOptimizerEngine;

class DatabaseQueryOptimizerEngineTest extends TestCase
{
    public function testFlagsSelectStarAndSuggestsIndex(): void
    {
        $optimizer = new DatabaseQueryOptimizerEngine();
        $sql = "SELECT * FROM user_sessions WHERE user_id = 42 AND status = 'active'";
        $res = $optimizer->analyzeAndOptimize($sql);

        $this->assertFalse($res['is_optimal']);
        $this->assertGreaterThan(0, $res['recommendations_count']);
        $this->assertNotEmpty($res['suggested_indexes']);
        $this->assertStringContainsString('CREATE INDEX `idx_user_id_status` ON `user_sessions`', $res['suggested_indexes'][0]);
    }
}