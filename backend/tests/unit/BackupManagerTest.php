<?php

use PHPUnit\Framework\TestCase;
use Atom\Backup\BackupManager;

class BackupManagerTest extends TestCase
{
    private string $testBackupDir;

    protected function setUp(): void
    {
        $this->testBackupDir = sys_get_temp_dir() . '/atom_test_backups_' . time();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testBackupDir)) {
            $files = glob($this->testBackupDir . '/*');
            foreach ($files as $f) @unlink($f);
            @rmdir($this->testBackupDir);
        }
    }

    public function testBackupCreationAndArchiveValidation()
    {
        $manager = new BackupManager(dirname($this->testBackupDir));
        $res = $manager->createBackup($this->testBackupDir);

        $this->assertTrue($res['success']);
        $this->assertFileExists($res['archive_path']);
        $this->assertGreaterThan(0, $res['file_size']);

        $isValid = $manager->validateArchive($res['archive_path']);
        $this->assertTrue($isValid);
    }

    public function testDatasetExport()
    {
        $manager = new BackupManager(dirname($this->testBackupDir));
        $exportPath = $this->testBackupDir . '/dataset.json';
        if (!is_dir($this->testBackupDir)) {
            mkdir($this->testBackupDir, 0755, true);
        }

        $resultPath = $manager->exportDataset($exportPath);

        $this->assertFileExists($resultPath);
        $content = json_decode(file_get_contents($resultPath), true);
        $this->assertEquals('ATOM AI Core', $content['platform']);
        $this->assertArrayHasKey('memories', $content);
    }
}
