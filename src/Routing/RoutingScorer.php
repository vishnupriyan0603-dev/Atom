<?php

namespace Atom\Routing;

class RoutingScorer
{
    /**
     * Calculates composite score for a routing candidate based on evaluation quality and health.
     */
    public function scoreCandidate(RoutingCandidate $candidate, array $classifiedRequest): float
    {
        if (!$candidate->enabled) {
            return 0.0;
        }

        $evalScore   = $candidate->evaluationScore;
        $healthScore = $candidate->healthScore;

        $compositeScore = ($evalScore * 0.6) + ($healthScore * 0.4);
        return round($compositeScore, 4);
    }
}
