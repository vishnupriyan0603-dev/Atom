<?php

namespace Atom\DevOps;

class ContinuousBenchmarkGovernor
{
    private array $benchmarkHistory = [];

    /**
     * Run high-precision benchmark on a callable task.
     */
    public function benchmark(callable $task, int $iterations = 50, string $taskName = 'Unnamed Task'): array
    {
        if ($iterations <= 0) {
            $iterations = 1;
        }

        gc_collect_cycles();
        $startMem = memory_get_usage(true);
        $peakMem = memory_get_peak_usage(true);
        $latencies = [];

        for ($i = 0; $i < $iterations; $i++) {
            $t0 = microtime(true);
            $task();
            $t1 = microtime(true);
            $latencies[] = ($t1 - $t0) * 1000.0; // ms
        }

        gc_collect_cycles();
        $endMem = memory_get_usage(true);
        $postPeakMem = memory_get_peak_usage(true);

        sort($latencies);
        $count = count($latencies);
        $avg = array_sum($latencies) / $count;
        $min = $latencies[0];
        $max = $latencies[$count - 1];
        $p50 = $latencies[(int)floor($count * 0.50)];
        $p90 = $latencies[(int)floor($count * 0.90)];
        $p95 = $latencies[(int)floor($count * 0.95)];
        $p99 = $latencies[(int)floor($count * 0.99)];

        $memDelta = $endMem - $startMem;
        $isLeaking = ($memDelta > (1024 * 1024)); // > 1MB retained memory

        $result = [
            'task_name' => $taskName,
            'iterations' => $iterations,
            'latency_ms' => [
                'min' => round($min, 3),
                'avg' => round($avg, 3),
                'p50' => round($p50, 3),
                'p90' => round($p90, 3),
                'p95' => round($p95, 3),
                'p99' => round($p99, 3),
                'max' => round($max, 3),
            ],
            'memory' => [
                'start_bytes' => $startMem,
                'end_bytes' => $endMem,
                'delta_bytes' => $memDelta,
                'peak_bytes' => $postPeakMem,
                'leak_detected' => $isLeaking,
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->benchmarkHistory[] = $result;

        return $result;
    }

    public function getHistory(): array
    {
        return $this->benchmarkHistory;
    }
}