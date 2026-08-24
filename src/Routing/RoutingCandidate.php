<?php

namespace Atom\Routing;

class RoutingCandidate
{
    public int $id;
    public int $policyId;
    public string $targetType;
    public string $targetId;
    public string $provider;
    public array $capabilities;
    public float $evaluationScore;
    public float $healthScore;
    public int $trafficWeight;
    public bool $enabled;

    public function __construct(array $data)
    {
        $this->id              = (int)($data['id'] ?? 0);
        $this->policyId        = (int)($data['policy_id'] ?? 1);
        $this->targetType      = $data['target_type'] ?? 'model';
        $this->targetId        = (string)($data['target_id'] ?? 'gemini-1.5-flash');
        $this->provider        = $data['provider'] ?? 'gemini';
        $this->capabilities    = is_array($data['capabilities'] ?? null) ? $data['capabilities'] : (json_decode($data['capabilities_json'] ?? '[]', true) ?: []);
        $this->evaluationScore = (float)($data['evaluation_score'] ?? 0.95);
        $this->healthScore     = (float)($data['health_score'] ?? 1.0);
        $this->trafficWeight   = (int)($data['traffic_weight'] ?? 100);
        $this->enabled         = !empty($data['enabled']);
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'policy_id'         => $this->policyId,
            'target_type'       => $this->targetType,
            'target_id'         => $this->targetId,
            'provider'          => $this->provider,
            'capabilities_json' => json_encode($this->capabilities),
            'evaluation_score'  => $this->evaluationScore,
            'health_score'      => $this->healthScore,
            'traffic_weight'    => $this->trafficWeight,
            'enabled'           => $this->enabled ? 1 : 0,
        ];
    }
}
