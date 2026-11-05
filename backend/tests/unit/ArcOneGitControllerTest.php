<?php

use PHPUnit\Framework\TestCase;
use Atom\Arc\ArcOneGitController;

require_once dirname(__DIR__, 2) . '/../src/Arc/ArcOneGitController.php';

/**
 * Unit tests for ARC ONE — Git Controller.
 */
class ArcOneGitControllerTest extends TestCase
{
    private ArcOneGitController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ArcOneGitController();
    }

    public function testIsGitRepositoryReturnsStructuredResult(): void
    {
        $res = $this->controller->isGitRepository();
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('is_repo', $res);
        $this->assertTrue($res['is_repo']);
        $this->assertNotEmpty($res['repo_path']);
    }

    public function testStatusReturnsBranchAndFileCategories(): void
    {
        $res = $this->controller->status();
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['branch']);
        $this->assertIsBool($res['clean']);
        $this->assertIsArray($res['staged']);
        $this->assertIsArray($res['unstaged']);
        $this->assertIsArray($res['untracked']);
        $this->assertArrayHasKey('staged_count', $res);
    }

    public function testChangedFilesListsAllModifications(): void
    {
        $res = $this->controller->changedFiles();
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertIsArray($res['files']);
        $this->assertArrayHasKey('total_changed', $res);
    }

    public function testDiffReturnsStructuredDiffSummary(): void
    {
        $res = $this->controller->diff();
        $this->assertIsArray($res);
        $this->assertTrue($res['success']);
        $this->assertArrayHasKey('summary', $res);
        $this->assertArrayHasKey('has_diff', $res);
        $this->assertArrayHasKey('diff', $res);
        $this->assertFalse($res['staged']);
    }

    public function testInvalidRepoPathFailsGracefully(): void
    {
        $invalid = new ArcOneGitController(__DIR__ . '/non_existent_folder_xyz_123');
        $res = $invalid->status();
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('not a Git repository', $res['error']);
    }

    public function testCommitRejectsEmptyMessage(): void
    {
        $res = $this->controller->commit('   ');
        $this->assertIsArray($res);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('cannot be empty', $res['error']);
    }

    public function testActionLogTracksOperations(): void
    {
        $this->controller->isGitRepository();
        $this->controller->status();
        $log = $this->controller->getActionLog();
        $this->assertCount(3, $log);
    }
}

