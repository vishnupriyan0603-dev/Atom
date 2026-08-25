<?php

namespace App\Controllers\Api;

use Atom\Vision\VideoKeyframeSegmenterEngine;

/**
 * VideoSegmenter API Controller — Phase 82
 */
class VideoSegmenter extends BaseApiController
{
    private static ?VideoKeyframeSegmenterEngine $engine = null;

    private function getEngine(): VideoKeyframeSegmenterEngine
    {
        if (self::$engine === null) {
            self::$engine = new VideoKeyframeSegmenterEngine();
        }
        return self::$engine;
    }

    /**
     * POST /api/vision/video/segment
     */
    public function segment()
    {
        $json = $this->request->getJSON(true) ?? [];
        $frames = $json['frames'] ?? [
            ['timestamp_s' => 0.0, 'luminance' => 0.2, 'entropy' => 0.4],
            ['timestamp_s' => 1.0, 'luminance' => 0.22, 'entropy' => 0.42],
            ['timestamp_s' => 2.0, 'luminance' => 0.85, 'entropy' => 0.90], // Scene Cut
            ['timestamp_s' => 3.0, 'luminance' => 0.87, 'entropy' => 0.88],
        ];
        $thresh = (float) ($json['cut_threshold'] ?? 0.35);

        $engine = $this->getEngine();
        $res = $engine->segmentScenes($frames, $thresh);

        return $this->respondSuccess($res, 'Video segmented into scenes');
    }

    /**
     * POST /api/vision/video/keyframes
     */
    public function keyframes()
    {
        $json = $this->request->getJSON(true) ?? [];
        $frames = $json['frames'] ?? [];
        $k = (int) ($json['top_k'] ?? 3);

        $engine = $this->getEngine();
        $res = $engine->extractTopKeyframes($frames, $k);

        return $this->respondSuccess($res, 'Keyframes extracted');
    }

    /**
     * GET /api/vision/video/codecs
     */
    public function codecs()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getSupportedCodecs(), 'Supported codecs and video standards');
    }
}
