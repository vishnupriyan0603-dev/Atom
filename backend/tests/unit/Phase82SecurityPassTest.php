<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Vision\VideoKeyframeSegmenterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 82 — Phase82SecurityPassTest security & safety tests (5 tests).
 */
class Phase82SecurityPassTest extends TestCase
{
    private SecretRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redactor = new SecretRedactor();
    }

    public function testHighVolumeFrameStreamThroughput(): void
    {
        $engine = new VideoKeyframeSegmenterEngine($this->redactor);
        $frames = [];
        for ($i = 0; $i < 500; $i++) {
            $frames[] = [
                'timestamp_s' => $i * 0.033, // 30 fps
                'luminance' => ($i % 30 === 0) ? 0.9 : 0.2, // Cut every 1s
                'entropy' => 0.5,
            ];
        }

        $startTime = microtime(true);
        $res = $engine->segmentScenes($frames, 0.35);
        $duration = microtime(true) - $startTime;

        $this->assertTrue($res['success']);
        $this->assertGreaterThan(10, $res['total_scenes']);
        $this->assertLessThan(1.0, $duration);
    }

    public function testNegativeOrExtremeEntropySafety(): void
    {
        $engine = new VideoKeyframeSegmenterEngine($this->redactor);
        $frames = [
            ['timestamp_s' => 0.0, 'luminance' => -10.0, 'entropy' => -50.0],
            ['timestamp_s' => 1.0, 'luminance' => 100.0, 'entropy' => 999.0],
        ];

        $res = $engine->segmentScenes($frames);
        $this->assertTrue($res['success']);
    }

    public function testTopKeyframesRequestingMoreThanAvailable(): void
    {
        $engine = new VideoKeyframeSegmenterEngine($this->redactor);
        $frames = [
            ['timestamp_s' => 0.0, 'entropy' => 0.5],
        ];

        $res = $engine->extractTopKeyframes($frames, 100);
        $this->assertTrue($res['success']);
        $this->assertSame(1, $res['extracted_count']);
    }

    public function testDurationNeverNegative(): void
    {
        $engine = new VideoKeyframeSegmenterEngine($this->redactor);
        $frames = [
            ['timestamp_s' => 5.0, 'luminance' => 0.2, 'entropy' => 0.4],
            ['timestamp_s' => 2.0, 'luminance' => 0.2, 'entropy' => 0.4], // inverted timestamp
        ];

        $res = $engine->segmentScenes($frames);
        $this->assertTrue($res['success']);
        $this->assertGreaterThanOrEqual(0.01, $res['scenes'][0]['duration_s']);
    }

    public function testNoDangerousEvalOrShellExecutionInVisionSubsystem(): void
    {
        $files = [
            'src/Vision/VideoKeyframeSegmenterEngine.php',
            'src/Vision/VisionEngine.php',
            'src/Vision/VisualLayoutSynthesizer.php',
        ];

        foreach ($files as $file) {
            $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($path);
            $content = file_get_contents($path);

            $this->assertDoesNotMatchRegularExpression('/(?<!->)\b(eval|exec|passthru|shell_exec|system)\s*\(/i', $content);
        }
    }
}
