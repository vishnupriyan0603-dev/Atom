<?php

namespace Tests\Unit;

use Atom\Brain\AtomBrain;
use Atom\Brain\ContextBuilder;
use Atom\Brain\IntentDetector;
use Atom\Database\Connection;
use Atom\Knowledge\KnowledgeSearch;
use Atom\Memory\MemoryManager;
use Atom\PersonalModel\ModelManager;
use Atom\PersonalModel\ModelResponse;
use Atom\Project\ProjectScanner;
use Atom\Security\SecretRedactor;
use Atom\Tools\ToolManager;
use PHPUnit\Framework\TestCase;

final class AtomBrainPrivacyQuestionTest extends TestCase
{
    private string $workspaceRoot;

    protected function setUp(): void
    {
        if (!class_exists(\Atom\Config\Config::class)) {
            require_once dirname(__DIR__, 3) . '/config/config.php';
        }
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'atom-privacy-test-' . uniqid('', true);
        mkdir($this->workspaceRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->workspaceRoot)) {
            rmdir($this->workspaceRoot);
        }
    }

    public function testRecognizedTrackingQuestionDoesNotCallProviderOrToolsAndIsAddedToHistory(): void
    {
        $modelManager = $this->createMock(ModelManager::class);
        $modelManager->expects($this->never())->method('generateForRole');
        $toolManager = $this->createMock(ToolManager::class);
        $toolManager->expects($this->once())->method('hasTool')->with('mylife_os')->willReturn(true);
        $toolManager->expects($this->never())->method('executeTool');
        $toolManager->expects($this->never())->method('authorizeAction');

        $brain = $this->createBrain($modelManager, $toolManager);
        $history = [];
        $reply = $brain->process('Did you track me?', $history);

        $this->assertStringContainsString('No—this answer did not track or locate you', $reply);
        $this->assertStringContainsString('explicitly approve', $reply);
        $this->assertSame('Did you track me?', $history[0]['content']);
        $this->assertSame($reply, $history[1]['content']);
    }

    public function testUnrecognizedQuestionStillReturnsTheProviderFailure(): void
    {
        $modelManager = $this->createMock(ModelManager::class);
        $modelManager->expects($this->once())
            ->method('generateForRole')
            ->with('primary', $this->isType('array'))
            ->willReturn(new ModelResponse(false, '', 'cURL Error: Operation timed out'));
        $toolManager = $this->createMock(ToolManager::class);
        $toolManager->expects($this->once())->method('hasTool')->with('mylife_os')->willReturn(false);
        $toolManager->expects($this->never())->method('executeTool');
        $toolManager->expects($this->never())->method('authorizeAction');

        $brain = $this->createBrain($modelManager, $toolManager);
        $history = [];
        $reply = $brain->process('Will it rain tomorrow?', $history);

        $this->assertStringContainsString('cURL Error: Operation timed out', $reply);
        $this->assertStringContainsString('Switch active provider', $reply);
        $this->assertSame([], $history);
    }

    private function createBrain(ModelManager $modelManager, ToolManager $toolManager): AtomBrain
    {
        $redactor = new SecretRedactor();
        $connection = new Connection();
        $memory = new MemoryManager(null, $this->workspaceRoot);

        return new AtomBrain(
            $modelManager,
            new IntentDetector(),
            new ContextBuilder($this->workspaceRoot),
            new ProjectScanner($this->workspaceRoot),
            $redactor,
            $memory,
            new KnowledgeSearch($connection),
            $toolManager
        );
    }
}
