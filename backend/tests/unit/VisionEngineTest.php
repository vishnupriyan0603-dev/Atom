<?php

use PHPUnit\Framework\TestCase;
use Atom\Vision\VisionEngine;
use Atom\Vision\MultiModalPayload;

/**
 * Phase 24 — VisionEngine unit tests (6 tests).
 */
class VisionEngineTest extends TestCase
{
    private VisionEngine $engine;
    private MultiModalPayload $samplePayload;

    protected function setUp(): void
    {
        $this->engine = new VisionEngine();
        $dummyBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $this->samplePayload = new MultiModalPayload($dummyBase64, 'image/png', 'test_pixel.png');
    }

    public function testGeneralImageAnalysisReturnsSuccess(): void
    {
        $result = $this->engine->analyze($this->samplePayload, 'Describe this image', 'general_analysis');
        $this->assertTrue($result['success']);
        $this->assertSame('general_analysis', $result['task_type']);
        $this->assertArrayHasKey('analysis', $result['data']);
    }

    public function testScreenshotDebugTaskType(): void
    {
        $result = $this->engine->debugScreenshot($this->samplePayload, 'Stack trace in LoginController.php');
        $this->assertTrue($result['success']);
        $this->assertSame('screenshot_debug', $result['task_type']);
        $this->assertStringContainsString('Screenshot Diagnostic Report', $result['data']['analysis']);
    }

    public function testUiToCodeTaskType(): void
    {
        $result = $this->engine->generateUiCode($this->samplePayload, 'Bootstrap 5');
        $this->assertTrue($result['success']);
        $this->assertSame('ui_to_code', $result['task_type']);
        $this->assertStringContainsString('<div class="card', $result['data']['analysis']);
    }

    public function testDiagramParseTaskType(): void
    {
        $result = $this->engine->analyze($this->samplePayload, '', 'diagram_parse');
        $this->assertTrue($result['success']);
        $this->assertSame('diagram_parse', $result['task_type']);
        $this->assertStringContainsString('Diagram Architecture', $result['data']['analysis']);
    }

    public function testRejectsNonImageMimeType(): void
    {
        $invalidPayload = new MultiModalPayload('dGVzdA==', 'application/pdf', 'doc.pdf');
        $result = $this->engine->analyze($invalidPayload);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported file type', $result['error']);
    }

    public function testPayloadFromArrayStructure(): void
    {
        $arr = $this->samplePayload->toArray();
        $this->assertSame('image/png', $arr['mime_type']);
        $this->assertSame('test_pixel.png', $arr['file_name']);
        $this->assertTrue($arr['is_image']);
    }
}
