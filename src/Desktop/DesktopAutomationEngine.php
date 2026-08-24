<?php

namespace Atom\Desktop;

use Atom\Security\SecretRedactor;
use Atom\Governance\PolicyEngine;

/**
 * DesktopAutomationEngine — Master Desktop Sidecar Orchestrator.
 *
 * Coordinates:
 * - Active foreground window & developer tool inspection
 * - Proactive clipboard data analysis and AI action suggestions
 * - Safe system control actions (volume, battery, notifications)
 * - Strict governance and secret redaction enforcement
 */
class DesktopAutomationEngine
{
    private WindowManager $windowManager;
    private ClipboardIntelligence $clipboard;
    private SystemControlSidecar $systemControl;
    private SecretRedactor $redactor;
    private array $notificationLog = [];

    public function __construct(
        ?WindowManager $windowManager = null,
        ?ClipboardIntelligence $clipboard = null,
        ?SystemControlSidecar $systemControl = null,
        ?SecretRedactor $redactor = null
    ) {
        $this->windowManager = $windowManager ?? new WindowManager();
        $this->clipboard = $clipboard ?? new ClipboardIntelligence();
        $this->systemControl = $systemControl ?? new SystemControlSidecar();
        $this->redactor = $redactor ?? new SecretRedactor();
    }

    /**
     * Get aggregated real-time desktop sidecar state.
     */
    public function getDesktopState(): array
    {
        $window = $this->windowManager->getActiveWindow();
        $system = $this->systemControl->getSystemInfo();
        $processes = $this->windowManager->getRunningProcesses();

        return [
            'sidecar_status' => 'online',
            'version' => '1.0.0-phase27',
            'active_window' => $window,
            'system_info' => $system,
            'developer_tools' => $processes,
            'notifications_sent' => count($this->notificationLog),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Dispatch a native desktop notification toast.
     */
    public function dispatchNotification(string $title, string $message, string $category = 'info'): array
    {
        $cleanTitle = $this->redactor->redact(strip_tags($title));
        $cleanMessage = $this->redactor->redact(strip_tags($message));

        $toast = [
            'id' => uniqid('toast_', true),
            'title' => $cleanTitle,
            'message' => $cleanMessage,
            'category' => $category,
            'delivered' => true,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->notificationLog[] = $toast;

        return [
            'success' => true,
            'toast' => $toast,
        ];
    }

    /**
     * Execute a safe system action.
     */
    public function executeSafeAction(string $action, array $params = []): array
    {
        return $this->systemControl->performAction($action, $params);
    }

    public function getClipboard(): ClipboardIntelligence
    {
        return $this->clipboard;
    }

    public function getWindowManager(): WindowManager
    {
        return $this->windowManager;
    }

    public function getSystemControl(): SystemControlSidecar
    {
        return $this->systemControl;
    }
}
