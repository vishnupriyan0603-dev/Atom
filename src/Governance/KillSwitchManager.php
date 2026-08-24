<?php

namespace Atom\Governance;

class KillSwitchManager
{
    private static array $activeKillSwitches = [];

    public static function enableKillSwitch(string $targetType, string $targetId, string $reason = ''): void
    {
        $key = strtolower($targetType . ':' . $targetId);
        self::$activeKillSwitches[$key] = $reason ?: 'Emergency kill switch enabled';
    }

    public static function disableKillSwitch(string $targetType, string $targetId): void
    {
        $key = strtolower($targetType . ':' . $targetId);
        unset(self::$activeKillSwitches[$key]);
    }

    public static function isKilled(string $targetType, string $targetId): bool
    {
        $key = strtolower($targetType . ':' . $targetId);
        return isset(self::$activeKillSwitches[$key]);
    }
}
