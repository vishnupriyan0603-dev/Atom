<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Database\SchemaDriftDetectorEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 65 — SchemaDriftDetectorEngine unit tests (6 tests).
 */
class SchemaDriftDetectorEngineTest extends TestCase
{
    private SchemaDriftDetectorEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SchemaDriftDetectorEngine(new SecretRedactor());
    }

    public function testDetectsMissingTableDrift(): void
    {
        $current = ['users' => ['id' => 'INT']];
        $expected = [
            'users' => ['id' => 'INT'],
            'audit_logs' => ['id' => 'INT', 'event' => 'VARCHAR'],
        ];

        $res = $this->engine->detectDrift($current, $expected);
        $this->assertTrue($res['success']);
        $this->assertTrue($res['drift_detected']);
        $this->assertSame(1, $res['drift_count']);
        $this->assertSame('MISSING_TABLE', $res['drifts'][0]['type']);
    }

    public function testDetectsMissingColumnDrift(): void
    {
        $current = ['users' => ['id' => 'INT', 'email' => 'VARCHAR']];
        $expected = ['users' => ['id' => 'INT', 'email' => 'VARCHAR', 'is_verified' => 'BOOLEAN']];

        $res = $this->engine->detectDrift($current, $expected);
        $this->assertTrue($res['success']);
        $this->assertTrue($res['drift_detected']);
        $this->assertArrayHasKey('is_verified', $res['drifts'][0]['missing_columns']);
    }

    public function testIdenticalSchemasScoreZeroDrift(): void
    {
        $schema = ['users' => ['id' => 'INT', 'email' => 'VARCHAR']];
        $res = $this->engine->detectDrift($schema, $schema);

        $this->assertTrue($res['success']);
        $this->assertFalse($res['drift_detected']);
        $this->assertSame('SCHEMA_IN_SYNC', $res['status']);
    }

    public function testSynthesizeMigrationGeneratesValidPhpCode(): void
    {
        $drifts = [
            [
                'type' => 'MISSING_TABLE',
                'table' => 'audit_logs',
                'columns' => ['id' => 'INT', 'event' => 'VARCHAR'],
            ],
            [
                'type' => 'COLUMN_DRIFT',
                'table' => 'users',
                'missing_columns' => ['mfa_token' => 'VARCHAR'],
            ]
        ];

        $code = $this->engine->synthesizeMigration($drifts, 'SyncPlatformDrifts');
        $this->assertStringContainsString('class SyncPlatformDrifts extends Migration', $code);
        $this->assertStringContainsString('public function up(): void', $code);
        $this->assertStringContainsString('public function down(): void', $code);
        $this->assertStringContainsString("createTable('audit_logs'", $code);
        $this->assertStringContainsString("addColumn('users'", $code);
    }

    public function testEmptyExpectedSchemaFailsGracefully(): void
    {
        $res = $this->engine->detectDrift(['users' => ['id' => 'INT']], []);
        $this->assertFalse($res['success']);
        $this->assertFalse($res['drift_detected']);
    }

    public function testDetectsTypeMismatchDrift(): void
    {
        $current = ['users' => ['id' => 'INT', 'status' => 'INT']];
        $expected = ['users' => ['id' => 'INT', 'status' => 'VARCHAR']];

        $res = $this->engine->detectDrift($current, $expected);
        $this->assertTrue($res['success']);
        $this->assertTrue($res['drift_detected']);
        $this->assertArrayHasKey('status', $res['drifts'][0]['type_mismatches']);
    }
}
