<?php

namespace App\Controllers\Api;

use Atom\Desktop\DesktopAutomationEngine;
use Atom\Desktop\ClipboardIntelligence;

/**
 * Desktop API Controller — Phase 27
 *
 * Endpoints:
 * - GET  /api/v1/desktop/status            — Live OS sidecar status
 * - POST /api/v1/desktop/clipboard/analyze — Analyze clipboard buffer
 * - POST /api/v1/desktop/window/focus      — Active window report
 * - POST /api/v1/desktop/notify            — Send native toast notification
 * - POST /api/v1/desktop/action            — Execute safe OS action
 */
class Desktop extends BaseApiController
{
    private static ?DesktopAutomationEngine $engineInstance = null;

    private function getEngine(): DesktopAutomationEngine
    {
        if (self::$engineInstance === null) {
            self::$engineInstance = new DesktopAutomationEngine();
        }
        return self::$engineInstance;
    }

    /**
     * GET /api/v1/desktop/status
     */
    public function status()
    {
        $engine = $this->getEngine();
        return $this->respondSuccess($engine->getDesktopState(), 'Desktop status retrieved');
    }

    /**
     * POST /api/v1/desktop/clipboard/analyze
     */
    public function analyzeClipboard()
    {
        $json = $this->request->getJSON(true) ?? [];
        $content = $json['content'] ?? '';

        $clipboard = new ClipboardIntelligence();
        $result = $clipboard->analyzeClipboard($content);

        return $this->respondSuccess($result, 'Clipboard analyzed');
    }

    /**
     * POST /api/v1/desktop/window/focus
     */
    public function focusWindow()
    {
        $engine = $this->getEngine();
        $window = $engine->getWindowManager()->getActiveWindow();

        return $this->respondSuccess($window, 'Window focus updated');
    }

    /**
     * POST /api/v1/desktop/notify
     */
    public function notify()
    {
        $json = $this->request->getJSON(true) ?? [];
        $title = $json['title'] ?? 'ATOM Assistant';
        $message = $json['message'] ?? 'Notification from ATOM';
        $category = $json['category'] ?? 'info';

        $engine = $this->getEngine();
        $result = $engine->dispatchNotification($title, $message, $category);

        return $this->respondSuccess($result, 'Notification dispatched');
    }

    /**
     * POST /api/v1/desktop/action
     */
    public function action()
    {
        $json = $this->request->getJSON(true) ?? [];
        $action = $json['action'] ?? '';
        $params = $json['params'] ?? [];

        if (empty($action)) {
            return $this->respondError('Missing action parameter', 400);
        }

        $engine = $this->getEngine();
        $result = $engine->executeSafeAction($action, $params);

        if (!$result['success']) {
            return $this->respondError($result['error'] ?? 'Action execution failed', 400);
        }

        return $this->respondSuccess($result, 'Action executed successfully');
    }
}
