<?php

namespace Atom\Brain\Device;

/**
 * DeviceAbstraction — detect and describe the runtime device context.
 *
 * Device types
 * ------------
 * cli     — PHP CLI (`php atom.php`)
 * web     — Apache/nginx HTTP request (web admin, API)
 * flutter — Mobile client (detected from X-Atom-Client header or env)
 * wpf     — Windows desktop client (detected from X-Atom-Client header or env)
 *
 * Priority: ATOM_DEVICE env var → X-Atom-Client header → PHP_SAPI detection.
 */
class DeviceAbstraction
{
    private string $deviceType;

    public function __construct(?string $override = null)
    {
        $this->deviceType = $override ?? $this->detect();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────────────

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    /**
     * Get format preferences for this device.
     * Used by PersonalityEngine and AwarenessEngine to tune output.
     */
    public function getFormatPreferences(): array
    {
        return match ($this->deviceType) {
            'cli'     => ['ansi' => true,  'markdown' => true,  'compact' => false],
            'web'     => ['ansi' => false, 'markdown' => true,  'compact' => false],
            'flutter' => ['ansi' => false, 'markdown' => true,  'compact' => true],
            'wpf'     => ['ansi' => false, 'markdown' => true,  'compact' => false],
            default   => ['ansi' => false, 'markdown' => true,  'compact' => false],
        };
    }

    public function getDeviceContext(): array
    {
        return [
            'device_type'        => $this->deviceType,
            'format_preferences' => $this->getFormatPreferences(),
            'is_mobile'          => $this->deviceType === 'flutter',
            'is_desktop'         => in_array($this->deviceType, ['cli', 'wpf'], true),
            'is_web'             => $this->deviceType === 'web',
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Internal
    // ──────────────────────────────────────────────────────────────────────────

    private function detect(): string
    {
        // 1. Explicit environment override
        $envDevice = getenv('ATOM_DEVICE');
        if ($envDevice && in_array(strtolower($envDevice), ['cli', 'web', 'flutter', 'wpf'], true)) {
            return strtolower($envDevice);
        }

        // 2. HTTP header (from Flutter / WPF clients)
        $clientHeader = $_SERVER['HTTP_X_ATOM_CLIENT'] ?? '';
        if ($clientHeader) {
            $clientLower = strtolower($clientHeader);
            if (str_contains($clientLower, 'flutter')) return 'flutter';
            if (str_contains($clientLower, 'wpf'))     return 'wpf';
        }

        // 3. PHP SAPI
        if (PHP_SAPI === 'cli') {
            return 'cli';
        }

        return 'web';
    }
}
