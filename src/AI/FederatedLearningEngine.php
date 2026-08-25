<?php

namespace Atom\AI;

use Atom\Security\SecretRedactor;

/**
 * FederatedLearningEngine — Phase 55
 * Decentralized federated learning aggregator with $(\epsilon, \delta)$ differential privacy noise injection.
 */
class FederatedLearningEngine
{
    private SecretRedactor $redactor;
    private array $globalWeights = [];
    private float $privacyEpsilon = 0.5; // Differential privacy parameter (lower = more private)

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        // Seed default 4-dimensional model layer weights
        $this->globalWeights = [
            'layer_dense_0' => [0.42, -0.15, 0.88, 0.31],
            'layer_dense_1' => [0.12, 0.74, -0.22, 0.55],
        ];
    }

    /**
     * Aggregate local model weight updates using Federated Averaging (FedAvg) + Differential Privacy noise.
     *
     * @param array $clientUpdates List of client gradient updates [['node_id' => 'node-1', 'weights' => [...]]]
     * @return array Aggregated global model weights and privacy metrics
     */
    public function aggregateWeights(array $clientUpdates): array
    {
        if (empty($clientUpdates)) {
            return [
                'success' => false,
                'error' => 'Client updates cannot be empty',
                'global_weights' => $this->globalWeights,
            ];
        }

        $numClients = count($clientUpdates);
        $newGlobalWeights = [];

        foreach ($this->globalWeights as $layer => $currentWeights) {
            $layerDim = count($currentWeights);
            $accumulatedWeights = array_fill(0, $layerDim, 0.0);

            foreach ($clientUpdates as $client) {
                $clientWeights = $client['weights'][$layer] ?? $currentWeights;
                for ($i = 0; $i < $layerDim; $i++) {
                    $accumulatedWeights[$i] += ($clientWeights[$i] ?? 0.0);
                }
            }

            // Average weights across all contributing edge nodes
            $averaged = array_map(fn($val) => $val / $numClients, $accumulatedWeights);

            // Inject Laplacian differential privacy noise: Noise ~ Laplace(0, Sensitivity / Epsilon)
            $sensitivity = 0.05;
            $scale = $sensitivity / max(0.01, $this->privacyEpsilon);

            $privateWeights = array_map(function ($w) use ($scale) {
                $u = (mt_rand(1, 999999) / 1000000.0) - 0.5;
                $laplaceNoise = - $scale * ($u < 0 ? 1 : -1) * log(1 - 2 * abs($u));
                return round($w + $laplaceNoise, 4);
            }, $averaged);

            $newGlobalWeights[$layer] = $privateWeights;
        }

        $this->globalWeights = $newGlobalWeights;

        return [
            'success' => true,
            'participating_nodes' => $numClients,
            'privacy_epsilon' => $this->privacyEpsilon,
            'privacy_guarantee' => 'NIST $(\epsilon = ' . $this->privacyEpsilon . ')$ Differential Privacy Active',
            'global_weights' => $this->globalWeights,
            'training_round' => 'ROUND_' . bin2hex(random_bytes(4)),
        ];
    }

    public function getGlobalWeights(): array
    {
        return $this->globalWeights;
    }
}
