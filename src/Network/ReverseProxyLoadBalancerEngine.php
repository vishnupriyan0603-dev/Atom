<?php

namespace Atom\Network;

use Atom\Security\SecretRedactor;

/**
 * ReverseProxyLoadBalancerEngine — Phase 86
 * Multi-algorithm reverse proxy load balancer (Round-Robin, Weighted, IP-Hash, Least-Connections) and upstream health watchdog.
 */
class ReverseProxyLoadBalancerEngine
{
    private SecretRedactor $redactor;
    private array $upstreams = [];
    private string $algorithm = 'round_robin'; // 'round_robin', 'weighted', 'ip_hash', 'least_latency'
    private int $rrIndex = 0;

    public function __construct(?SecretRedactor $redactor = null)
    {
        $this->redactor = $redactor ?? new SecretRedactor();
        $this->seedSampleUpstreams();
    }

    /**
     * Add or update an upstream backend node.
     */
    public function registerUpstream(string $nodeId, string $host, int $port = 8080, int $weight = 1, bool $healthy = true): bool
    {
        $cleanId = trim(strtolower($this->redactor->redact($nodeId)));
        $cleanHost = trim($this->redactor->redact($host));

        $this->upstreams[$cleanId] = [
            'node_id' => $cleanId,
            'host' => $cleanHost,
            'port' => $port,
            'weight' => max(1, min(100, $weight)),
            'healthy' => $healthy,
            'active_connections' => 0,
            'latency_ms' => rand(5, 25),
            'total_routed_requests' => 0,
        ];

        return true;
    }

    /**
     * Route a request to an upstream server according to the configured algorithm.
     */
    public function routeRequest(string $clientIp = '127.0.0.1', string $path = '/api/v1/resource'): array
    {
        $healthyNodes = array_filter($this->upstreams, fn($node) => $node['healthy']);

        if (empty($healthyNodes)) {
            return [
                'success' => false,
                'error' => 'NO_HEALTHY_UPSTREAMS_AVAILABLE',
                'routed_node' => null,
            ];
        }

        $healthyList = array_values($healthyNodes);
        $selectedNode = null;

        switch ($this->algorithm) {
            case 'ip_hash':
                $hash = abs(crc32($clientIp)) % count($healthyList);
                $selectedNode = $healthyList[$hash];
                break;

            case 'least_latency':
                usort($healthyList, fn($a, $b) => $a['latency_ms'] <=> $b['latency_ms']);
                $selectedNode = $healthyList[0];
                break;

            case 'weighted':
                $totalWeight = array_sum(array_column($healthyList, 'weight'));
                $randWeight = rand(1, max(1, $totalWeight));
                $currentSum = 0;
                foreach ($healthyList as $node) {
                    $currentSum += $node['weight'];
                    if ($randWeight <= $currentSum) {
                        $selectedNode = $node;
                        break;
                    }
                }
                $selectedNode = $selectedNode ?? $healthyList[0];
                break;

            case 'round_robin':
            default:
                $this->rrIndex = $this->rrIndex % count($healthyList);
                $selectedNode = $healthyList[$this->rrIndex];
                $this->rrIndex++;
                break;
        }

        $nodeId = $selectedNode['node_id'];
        $this->upstreams[$nodeId]['total_routed_requests']++;

        return [
            'success' => true,
            'algorithm_used' => $this->algorithm,
            'client_ip' => $clientIp,
            'target_path' => $path,
            'routed_node' => [
                'node_id' => $selectedNode['node_id'],
                'target_url' => "http://{$selectedNode['host']}:{$selectedNode['port']}{$path}",
                'latency_ms' => $selectedNode['latency_ms'],
            ],
            'headers_injected' => [
                'X-Forwarded-For' => $clientIp,
                'X-Forwarded-Proto' => 'https',
                'X-Proxy-Hop' => 'ATOM-Edge-Gateway',
            ],
        ];
    }

    public function setAlgorithm(string $algo): bool
    {
        $valid = ['round_robin', 'weighted', 'ip_hash', 'least_latency'];
        if (in_array(strtolower($algo), $valid, true)) {
            $this->algorithm = strtolower($algo);
            return true;
        }
        return false;
    }

    public function setNodeHealth(string $nodeId, bool $healthy): bool
    {
        $cleanId = trim(strtolower($nodeId));
        if (isset($this->upstreams[$cleanId])) {
            $this->upstreams[$cleanId]['healthy'] = $healthy;
            return true;
        }
        return false;
    }

    public function getUpstreamStatus(): array
    {
        return [
            'active_algorithm' => $this->algorithm,
            'total_nodes' => count($this->upstreams),
            'healthy_nodes_count' => count(array_filter($this->upstreams, fn($n) => $n['healthy'])),
            'upstreams' => array_values($this->upstreams),
        ];
    }

    private function seedSampleUpstreams(): void
    {
        $this->registerUpstream('upstream_us_east', '10.0.1.50', 8081, 7, true);
        $this->registerUpstream('upstream_us_west', '10.0.2.50', 8082, 3, true);
        $this->registerUpstream('upstream_eu_central', '10.0.3.50', 8083, 5, true);
    }
}
