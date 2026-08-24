<?php

namespace App\Controllers\Api;

use Atom\Vision\VisionEngine;
use Atom\Vision\MultiModalPayload;

/**
 * Vision API Controller — Phase 24
 *
 * Endpoints:
 * - POST /api/v1/vision/analyze          — General image analysis / UI conversion
 * - POST /api/v1/vision/screenshot-debug — Debug screenshot with code error diagnosis
 */
class Vision extends BaseApiController
{
    /**
     * POST /api/v1/vision/analyze
     */
    public function analyze()
    {
        $json = $this->request->getJSON(true) ?? [];
        $base64 = $json['image_base64'] ?? '';
        $mimeType = $json['mime_type'] ?? 'image/png';
        $fileName = $json['file_name'] ?? 'upload.png';
        $prompt = $json['prompt'] ?? '';
        $taskType = $json['task_type'] ?? 'general_analysis';

        if (empty($base64)) {
            return $this->respondError('Missing image_base64 data', 400);
        }

        try {
            $payload = new MultiModalPayload($base64, $mimeType, $fileName);
            $engine = new VisionEngine();
            $result = $engine->analyze($payload, $prompt, $taskType);

            if (!$result['success']) {
                return $this->respondError($result['error'] ?? 'Vision analysis failed', 400);
            }

            return $this->respondSuccess($result, 'Vision analysis completed');
        } catch (\Throwable $e) {
            return $this->respondError('Failed to process image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/vision/screenshot-debug
     */
    public function debugScreenshot()
    {
        $json = $this->request->getJSON(true) ?? [];
        $base64 = $json['image_base64'] ?? '';
        $mimeType = $json['mime_type'] ?? 'image/png';
        $context = $json['context'] ?? '';

        if (empty($base64)) {
            return $this->respondError('Missing image_base64 data', 400);
        }

        try {
            $payload = new MultiModalPayload($base64, $mimeType, 'screenshot.png');
            $engine = new VisionEngine();
            $result = $engine->debugScreenshot($payload, $context);

            if (!$result['success']) {
                return $this->respondError($result['error'] ?? 'Screenshot diagnostic failed', 400);
            }

            return $this->respondSuccess($result, 'Screenshot diagnostic completed');
        } catch (\Throwable $e) {
            return $this->respondError('Diagnostic error: ' . $e->getMessage(), 500);
        }
    }
}
