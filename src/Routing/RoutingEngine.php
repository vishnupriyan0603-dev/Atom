<?php

namespace Atom\Routing;

use Atom\Telemetry\TelemetryManager;
use CodeIgniter\Database\BaseConnection;

class RoutingEngine
{
    private RequestClassifier $classifier;
    private CircuitBreaker $circuitBreaker;
    private RoutingScorer $scorer;

    public function __construct(
        ?RequestClassifier $classifier = null,
        ?CircuitBreaker $circuitBreaker = null,
        ?RoutingScorer $scorer = null
    ) {
        $this->classifier     = $classifier ?? new RequestClassifier();
        $this->circuitBreaker = $circuitBreaker ?? new CircuitBreaker();
        $this->scorer         = $scorer ?? new RoutingScorer();
    }

    private function getDb(): ?BaseConnection
    {
        try {
            return \Config\Database::connect();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Selects optimal candidate for request context with transparent failover.
     */
    public function selectCandidate(array $requestContext, array $candidates = []): array
    {
        $span = TelemetryManager::getInstance()->startSpan('routing.select');

        $classified = $this->classifier->classifyRequest($requestContext);

        if (empty($candidates)) {
            $candidates = [
                new RoutingCandidate([
                    'target_id'        => 'gemini-1.5-flash',
                    'provider'         => 'gemini',
                    'evaluation_score' => 0.96,
                    'health_score'     => 1.0,
                    'enabled'          => 1,
                ]),
                new RoutingCandidate([
                    'target_id'        => 'groq-llama3-70b',
                    'provider'         => 'groq',
                    'evaluation_score' => 0.92,
                    'health_score'     => 1.0,
                    'enabled'          => 1,
                ]),
            ];
        }

        $bestCandidate = null;
        $bestScore     = -1.0;
        $fallbackUsed  = false;

        foreach ($candidates as $cand) {
            if (!$this->circuitBreaker->canRoute($cand->provider)) {
                $fallbackUsed = true;
                continue;
            }

            $score = $this->scorer->scoreCandidate($cand, $classified);
            if ($score > $bestScore) {
                $bestScore     = $score;
                $bestCandidate = $cand;
            }
        }

        if ($bestCandidate === null) {
            $bestCandidate = $candidates[0] ?? new RoutingCandidate(['target_id' => 'gemini-1.5-flash']);
            $fallbackUsed  = true;
        }

        $result = [
            'selected_candidate' => $bestCandidate->targetId,
            'provider'           => $bestCandidate->provider,
            'score'              => $bestScore,
            'fallback_used'      => $fallbackUsed,
            'reason_codes'       => ['CAPABILITY_MATCH', 'EVALUATION_SCORE', 'HEALTH'],
        ];

        // Persist decision audit
        $db = $this->getDb();
        if ($db !== null) {
            try {
                $db->table($db->prefixTable('atom_routing_decisions'), true)->insert([
                    'user_id'            => 1,
                    'policy_id'          => 1,
                    'selected_candidate' => $bestCandidate->targetId,
                    'reason_codes_json'  => json_encode($result['reason_codes']),
                    'score'              => $bestScore,
                    'fallback_used'      => $fallbackUsed ? 1 : 0,
                    'latency_ms'         => 120,
                    'created_at'         => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {}
        }

        TelemetryManager::getInstance()->endSpan($span, 'ok');

        return $result;
    }
}
