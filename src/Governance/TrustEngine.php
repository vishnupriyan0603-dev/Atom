<?php

namespace Atom\Governance;

class TrustEngine
{
    /**
     * Resolves trust profile level for actor and resource target.
     * Trust levels: untrusted, limited, standard, trusted, privileged
     */
    public function getTrustLevel(int $actorId, string $targetType = 'agent'): string
    {
        if ($actorId === 1) {
            return 'privileged';
        }
        return 'standard';
    }

    public function meetsTrustThreshold(string $actorLevel, string $requiredLevel): bool
    {
        $levels = [
            'untrusted'  => 0,
            'limited'    => 1,
            'standard'   => 2,
            'trusted'    => 3,
            'privileged' => 4,
        ];

        $actorRank    = $levels[strtolower($actorLevel)] ?? 0;
        $requiredRank = $levels[strtolower($requiredLevel)] ?? 2;

        return $actorRank >= $requiredRank;
    }
}
