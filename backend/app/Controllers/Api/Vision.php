<?php

namespace App\Controllers\Api;

use Atom\Vision\VisionEngine;
use Atom\Vision\MultiModalPayload;
use Atom\Vision\NeuralCodeOcrEngine;
use Atom\Vision\VisualLayoutSynthesizer;
use Atom\Vision\DiagramSchemaSynthesizer;

/**
 * Vision API Controller — Phase 24 & Phase 42 Multi-Modal Vision Studio
 */
class Vision extends BaseApiController
{
    /**
     * POST /api/vision/analyze
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
     * POST /api/vision/screenshot-debug
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

    /**
     * POST /api/vision/ocr/code (Phase 42)
     */
    public function ocrCode()
    {
        $json = $this->request->getJSON(true) ?? [];
        $engine = new NeuralCodeOcrEngine();
        $result = $engine->extractCode($json, [
            'language' => $json['language'] ?? 'auto',
            'clean_indentation' => $json['clean_indentation'] ?? true,
        ]);

        if (!$result['success']) {
            return $this->respondError($result['error'] ?? 'Code OCR extraction failed', 400);
        }

        return $this->respondSuccess($result, 'Neural Code OCR completed');
    }

    /**
     * POST /api/vision/ui/synthesize (Phase 42)
     */
    public function synthesizeUi()
    {
        $json = $this->request->getJSON(true) ?? [];
        $synthesizer = new VisualLayoutSynthesizer();
        $result = $synthesizer->synthesize($json);

        return $this->respondSuccess($result, 'UI Code synthesized successfully');
    }

    /**
     * POST /api/vision/diagram/schema (Phase 42)
     */
    public function synthesizeSchema()
    {
        $json = $this->request->getJSON(true) ?? [];
        $synthesizer = new DiagramSchemaSynthesizer();
        $result = $synthesizer->synthesize($json);

        return $this->respondSuccess($result, 'Schema & Diagram synthesized successfully');
    }

    /**
     * GET /api/vision/presets (Phase 42)
     */
    public function presets()
    {
        return $this->respondSuccess([
            'frameworks' => [
                ['id' => 'bootstrap5', 'name' => 'Bootstrap 5 (Dark Glassmorphic)', 'badge' => 'Default'],
                ['id' => 'tailwind', 'name' => 'Tailwind CSS 3.x', 'badge' => 'Modern'],
                ['id' => 'vanilla', 'name' => 'Vanilla HTML5 + CSS3', 'badge' => 'Native'],
                ['id' => 'flutter', 'name' => 'Flutter Dart Widget Tree', 'badge' => 'Mobile'],
            ],
            'languages' => ['auto', 'php', 'javascript', 'typescript', 'python', 'sql', 'csharp', 'html', 'css'],
            'themes' => ['dark', 'glass', 'light'],
        ]);
    }
}
