<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Atom\Vision\VideoKeyframeSegmenterEngine;
use Atom\Security\SecretRedactor;

/**
 * Phase 82 — VideoKeyframeSegmenterEngine unit tests (6 tests).
 */
class VideoKeyframeSegmenterEngineTest extends TestCase
{
    private VideoKeyframeSegmenterEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new VideoKeyframeSegmenterEngine(new SecretRedactor());
    }

    public function testSegmentScenesDetectsOpticalCut(): void
    {
        $frames = [
            ['timestamp_s' => 0.0, 'luminance' => 0.20, 'entropy' => 0.40],
            ['timestamp_s' => 1.0, 'luminance' => 0.22, 'entropy' => 0.42],
            ['timestamp_s' => 2.0, 'luminance' => 0.85, 'entropy' => 0.90], // Cut here
            ['timestamp_s' => 3.0, 'luminance' => 0.88, 'entropy' => 0.89],
        ];

        $res = $this->engine->segmentScenes($frames, 0.35);

        $this->assertTrue($res['success']);
        $this->assertSame(2, $res['total_scenes']);
        $this->assertCount(2, $res['scenes']);
        $this->assertSame(1, $res['scenes'][0]['scene_number']);
        $this->assertSame(2, $res['scenes'][1]['scene_number']);
    }

    public function testExtractTopKeyframesByEntropy(): void
    {
        $frames = [
            ['timestamp_s' => 0.0, 'entropy' => 0.30],
            ['timestamp_s' => 1.0, 'entropy' => 0.95],
            ['timestamp_s' => 2.0, 'entropy' => 0.60],
        ];

        $res = $this->engine->extractTopKeyframes($frames, 2);

        $this->assertTrue($res['success']);
        $this->assertSame(2, $res['extracted_count']);
        $this->assertSame(0.95, $res['keyframes'][0]['entropy']);
    }

    public function testEmptyFramesSequenceFailsGracefully(): void
    {
        $res = $this->engine->segmentScenes([]);
        $this->assertFalse($res['success']);
        $this->assertSame(0, $res['total_scenes']);

        $kf = $this->engine->extractTopKeyframes([]);
        $this->assertFalse($kf['success']);
    }

    public function testSceneDurationCalculation(): void
    {
        $frames = [
            ['timestamp_s' => 10.0, 'luminance' => 0.5, 'entropy' => 0.5],
            ['timestamp_s' => 14.5, 'luminance' => 0.52, 'entropy' => 0.51],
        ];

        $res = $this->engine->segmentScenes($frames, 0.5);

        $this->assertTrue($res['success']);
        $this->assertSame(1, $res['total_scenes']);
        $this->assertSame(4.5, $res['scenes'][0]['duration_s']);
    }

    public function testGetSupportedCodecsReturnsContainersAndStandards(): void
    {
        $codecs = $this->engine->getSupportedCodecs();

        $this->assertArrayHasKey('codecs', $codecs);
        $this->assertArrayHasKey('containers', $codecs);
        $this->assertContains('H.264 / AVC', $codecs['codecs']);
    }

    public function testKeyframeSaliencePreservation(): void
    {
        $frames = [
            ['timestamp_s' => 0.0, 'luminance' => 0.3, 'entropy' => 0.2],
            ['timestamp_s' => 1.0, 'luminance' => 0.3, 'entropy' => 0.8], // best frame in scene
            ['timestamp_s' => 2.0, 'luminance' => 0.3, 'entropy' => 0.4],
        ];

        $res = $this->engine->segmentScenes($frames);
        $this->assertSame(0.8, $res['scenes'][0]['keyframe']['entropy']);
    }
}
