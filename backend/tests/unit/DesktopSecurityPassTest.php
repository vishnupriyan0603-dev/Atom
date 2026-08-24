<?php

use PHPUnit\Framework\TestCase;
use Atom\Desktop\DesktopAutomationEngine;
use Atom\Desktop\ClipboardIntelligence;
use Atom\Desktop\SystemControlSidecar;

/**
 * Phase 27 — DesktopSecurityPassTest (5 tests).
 *
 * Enforces safety boundaries for Desktop Automation & Sidecar operations:
 * - Secret redaction in clipboard previews
 * - Secret redaction and HTML tag stripping in notification toasts
 * - System control blocks unpermitted/dangerous OS actions
 * - Volume boundary clamping (0-100%)
 * - Safe system control execution under PolicyEngine
 */
class DesktopSecurityPassTest extends TestCase
{
    public function testSecretRedactionInClipboardPreview(): void
    {
        $clipboard = new ClipboardIntelligence();
        $input = "export OPENAI_API_KEY=\"sk-1234567890abcdef1234567890abcdef\";";
        $res = $clipboard->analyzeClipboard($input);

        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', $res['preview']);
        $this->assertStringContainsString('[REDACTED]', $res['preview']);
    }

    public function testNotificationPayloadStripsHtmlAndRedactsSecrets(): void
    {
        $engine = new DesktopAutomationEngine();
        $res = $engine->dispatchNotification(
            '<b>Alert</b>',
            'Your key is sk-1234567890abcdef1234567890abcdef'
        );

        $this->assertTrue($res['success']);
        $this->assertStringNotContainsString('<b>', $res['toast']['title']);
        $this->assertStringNotContainsString('sk-1234567890abcdef1234567890abcdef', $res['toast']['message']);
    }

    public function testBlocksDangerousSystemActions(): void
    {
        $sidecar = new SystemControlSidecar();
        $dangerousActions = ['shutdown', 'reboot', 'format_drive', 'delete_workspace', 'exec_shell'];

        foreach ($dangerousActions as $action) {
            $res = $sidecar->performAction($action);
            $this->assertFalse($res['success'], "Action '{$action}' must be blocked.");
            $this->assertStringContainsString('not permitted', $res['error']);
        }
    }

    public function testVolumeBoundaryEnforcement(): void
    {
        $sidecar = new SystemControlSidecar();
        // Volume up 15 times
        for ($i = 0; $i < 15; $i++) {
            $sidecar->performAction('volume_up');
        }
        $info = $sidecar->getSystemInfo();
        $this->assertLessThanOrEqual(100, $info['volume']['level']);

        // Volume down 20 times
        for ($i = 0; $i < 20; $i++) {
            $sidecar->performAction('volume_down');
        }
        $info2 = $sidecar->getSystemInfo();
        $this->assertGreaterThanOrEqual(0, $info2['volume']['level']);
    }

    public function testDesktopSidecarDoesNotExposeSensitiveEnvVariables(): void
    {
        $sidecar = new SystemControlSidecar();
        $info = $sidecar->getSystemInfo();
        $json = json_encode($info);

        $this->assertStringNotContainsString('DB_PASSWORD', $json);
        $this->assertStringNotContainsString('GEMINI_API_KEY', $json);
    }
}
