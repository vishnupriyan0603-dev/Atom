<?php

namespace Atom\Vision;

use Atom\Security\SecretRedactor;

/**
 * VideoKeyframeSegmenterEngine — Phase 82
 * Multi-modal video scene boundary detection, visual entropy analysis, and salience-weighted keyframe extractor.
 */
class VideoKeyframeSegmenterEngine
{
    private SecretRedactor $redactor;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Segment a sequence of video frame descriptors into scenes based on visual delta threshold.
     *
     * @param array $frames Array of frame objects: [ ['timestamp_s' => float, 'luminance' => float, 'color_vector' => array, 'entropy' => float] ]
     * @param float $cutThreshold Visual delta threshold (default 0.35)
     * @return array Segmented scenes with keyframes
     */
    public function segmentScenes(array $frames, float $cutThreshold = 0.35): array
    {
        if (empty($frames)) {
            return [
                'success' => false,
                'error' => 'Frame descriptors sequence cannot be empty',
                'scenes' => [],
                'total_scenes' => 0,
            ];
        }

        $scenes = [];
        $currentSceneFrames = [];
        $lastFrame = null;
        $sceneIdx = 1;

        foreach ($frames as $frame) {
            $isCut = false;

            if ($lastFrame !== null) {
                $deltaLuminance = abs($frame['luminance'] - $lastFrame['luminance']);
                $deltaEntropy = abs(($frame['entropy'] ?? 0.5) - ($lastFrame['entropy'] ?? 0.5));
                $visualDelta = ($deltaLuminance * 0.6) + ($deltaEntropy * 0.4);

                if ($visualDelta >= $cutThreshold) {
                    $isCut = true;
                }
            }

            if ($isCut && !empty($currentSceneFrames)) {
                $scenes[] = $this->buildSceneObject($sceneIdx++, $currentSceneFrames);
                $currentSceneFrames = [];
            }

            $currentSceneFrames[] = $frame;
            $lastFrame = $frame;
        }

        if (!empty($currentSceneFrames)) {
            $scenes[] = $this->buildSceneObject($sceneIdx, $currentSceneFrames);
        }

        return [
            'success' => true,
            'total_scenes' => count($scenes),
            'total_frames_processed' => count($frames),
            'cut_threshold' => $cutThreshold,
            'scenes' => $scenes,
        ];
    }

    /**
     * Extract top-K most informative keyframes across a video.
     */
    public function extractTopKeyframes(array $frames, int $topK = 3): array
    {
        if (empty($frames)) {
            return ['success' => false, 'keyframes' => []];
        }

        // Sort frames by visual entropy & salience descending
        $scored = $frames;
        usort($scored, fn($a, $b) => ($b['entropy'] ?? 0.5) <=> ($a['entropy'] ?? 0.5));

        $selected = array_slice($scored, 0, max(1, min(count($frames), $topK)));

        return [
            'success' => true,
            'requested_k' => $topK,
            'extracted_count' => count($selected),
            'keyframes' => $selected,
        ];
    }

    private function buildSceneObject(int $index, array $sceneFrames): array
    {
        $start = $sceneFrames[0]['timestamp_s'] ?? 0.0;
        $end = end($sceneFrames)['timestamp_s'] ?? $start;
        $duration = max(0.01, round($end - $start, 2));

        // Find highest entropy frame in scene as representative keyframe
        $bestFrame = $sceneFrames[0];
        foreach ($sceneFrames as $f) {
            if (($f['entropy'] ?? 0.0) > ($bestFrame['entropy'] ?? 0.0)) {
                $bestFrame = $f;
            }
        }

        return [
            'scene_number' => $index,
            'start_time_s' => $start,
            'end_time_s' => $end,
            'duration_s' => $duration,
            'frame_count' => count($sceneFrames),
            'keyframe' => [
                'timestamp_s' => $bestFrame['timestamp_s'] ?? $start,
                'luminance' => $bestFrame['luminance'] ?? 0.5,
                'entropy' => $bestFrame['entropy'] ?? 0.5,
            ],
        ];
    }

    public function getSupportedCodecs(): array
    {
        return [
            'codecs' => ['H.264 / AVC', 'H.265 / HEVC', 'AV1', 'VP9', 'ProRes 422'],
            'containers' => ['mp4', 'mkv', 'webm', 'mov'],
            'max_resolution' => '8K UHD (7680x4320)',
            'max_fps' => 120,
        ];
    }
}
