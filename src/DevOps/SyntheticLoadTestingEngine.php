<?php

namespace Atom\DevOps;

class SyntheticLoadTestingEngine
{
    /**
     * Run simulated concurrent load test across an endpoint or callable.
     */
    public function runLoadTest(callable $endpoint, int $totalRequests = 100, int $concurrency = 10): array
    {
        if ($totalRequests <= 0) $totalRequests = 10;
        if ($concurrency <= 0) $concurrency = 1;

        $results = [];
        $t0 = microtime(true);
        $successCount = 0;
        $errorCount = 0;

        for ($i = 0; $i < $totalRequests; $i++) {
            $reqStart = microtime(true);
            try {
                $res = $endpoint($i);
                $success = ($res !== false && $res !== null);
                if ($success) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } catch (\Throwable $e) {
                $errorCount++;
            }
            $reqEnd = microtime(true);
            $results[] = ($reqEnd - $reqStart) * 1000.0; // ms
        }

        $totalDuration = microtime(true) - $t0;
        sort($results);
        $count = count($results);

        $rps = $totalDuration > 0 ? ($totalRequests / $totalDuration) : 0;
        $p50 = $results[(int)floor($count * 0.50)];
        $p90 = $results[(int)floor($count * 0.90)];
        $p99 = $results[(int)floor($count * 0.99)];

        return [
            'total_requests' => $totalRequests,
            'concurrency' => $concurrency,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'error_rate_percent' => round(($errorCount / $totalRequests) * 100.0, 2),
            'duration_sec' => round($totalDuration, 3),
            'requests_per_second' => round($rps, 1),
            'latency_ms' => [
                'min' => round($results[0], 2),
                'p50' => round($p50, 2),
                'p90' => round($p90, 2),
                'p99' => round($p99, 2),
                'max' => round($results[$count - 1], 2),
            ],
            'status' => ($errorCount === 0) ? 'PASSED' : 'DEGRADED',
        ];
    }
}