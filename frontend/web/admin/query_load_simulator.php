<?php
// ATOM Web Admin — Phase 61: Autonomous Database Query Slow-Log Replay & Index Load Simulator
$pageTitle = "Query Load Simulator (Phase 61)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">Autonomous Database Query Load Simulator &amp; Benchmarker</h2>
        <p class="text-muted small mb-0">Phase 61: High-Concurrency SQL Traffic Simulator, Percentile Latency Profiler ($p50, p90, p99$) &amp; Index Impact Benchmarker</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="runLoadBenchmark()">
            <i class="bi bi-lightning-charge-fill me-1"></i> Run Stress Benchmark
        </button>
    </div>
</div>

<!-- Benchmark Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SPEEDUP MULTIPLIER</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricSpeedup" style="color: #34D399;">18.5x FASTER</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">THROUGHPUT (QPS)</div>
            <div class="fs-4 fw-bold text-info" id="metricQps">892.4 QPS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">P99 LATENCY (INDEXED)</div>
            <div class="fs-4 fw-bold text-success" id="metricP99">1.84 ms</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">UNINDEXED P99</div>
            <div class="fs-4 fw-bold text-danger" id="metricP99Before">25.60 ms</div>
        </div>
    </div>
</div>

<!-- Main Benchmarking Section -->
<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-info"><i class="bi bi-sliders me-2"></i>Workload Simulation Parameters</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TARGET SQL QUERY</label>
                    <textarea id="simSqlQuery" class="form-control bg-black text-white border-secondary small" rows="5" style="font-family: monospace;">SELECT id, user_id, amount, status
FROM orders
WHERE user_id = 42 AND status = 'COMPLETED'
ORDER BY created_at DESC;</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>CONCURRENT QUERY ITERATIONS</span>
                        <span id="iterValText" class="text-info fw-bold">100</span>
                    </label>
                    <input type="range" class="form-range" id="iterRange" min="50" max="500" step="50" value="100" oninput="document.getElementById('iterValText').innerText = this.value;">
                </div>
                <button class="btn btn-info text-dark fw-bold w-100" onclick="runLoadBenchmark()">
                    <i class="bi bi-play-fill me-1"></i> Execute Load Simulation
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-table me-2"></i>Percentile Latency Comparison ($p50, p90, p99$)</span>
                <span class="badge bg-success" id="benchmarkBadge">COMPLETED</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Metric</th>
                                <th class="text-danger">Unindexed (Full Scan)</th>
                                <th class="text-success">Indexed (B-Tree Seek)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold">Throughput (QPS)</td>
                                <td class="text-danger" id="tblQpsBefore">45.2 QPS</td>
                                <td class="text-success fw-bold" id="tblQpsAfter">892.4 QPS (+1874%)</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">p50 Latency (Median)</td>
                                <td class="text-danger" id="tblP50Before">21.80 ms</td>
                                <td class="text-success fw-bold" id="tblP50After">1.12 ms</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">p90 Latency</td>
                                <td class="text-danger" id="tblP90Before">24.10 ms</td>
                                <td class="text-success fw-bold" id="tblP90After">1.48 ms</td>
                            </tr>
                            <tr>
                                <td class="fw-bold">p99 Latency (Tail)</td>
                                <td class="text-danger" id="tblP99Before">25.60 ms</td>
                                <td class="text-success fw-bold" id="tblP99After">1.84 ms</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function runLoadBenchmark() {
    const sql = document.getElementById('simSqlQuery').value;
    const iters = parseInt(document.getElementById('iterRange').value, 10);

    try {
        const res = await apiFetch('/database/load-simulator/run', {
            method: 'POST',
            body: JSON.stringify({ sql: sql, iterations: iters })
        });

        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricSpeedup').innerText = data.speedup_multiplier;
            document.getElementById('metricQps').innerText = `${data.after_indexed.qps} QPS`;
            document.getElementById('metricP99').innerText = `${data.after_indexed.p99_latency_ms} ms`;
            document.getElementById('metricP99Before').innerText = `${data.before_unindexed.p99_latency_ms} ms`;

            document.getElementById('tblQpsBefore').innerText = `${data.before_unindexed.qps} QPS`;
            document.getElementById('tblQpsAfter').innerText = `${data.after_indexed.qps} QPS (+${data.throughput_gain_pct}%)`;

            document.getElementById('tblP50Before').innerText = `${data.before_unindexed.p50_latency_ms} ms`;
            document.getElementById('tblP50After').innerText = `${data.after_indexed.p50_latency_ms} ms`;

            document.getElementById('tblP90Before').innerText = `${data.before_unindexed.p90_latency_ms} ms`;
            document.getElementById('tblP90After').innerText = `${data.after_indexed.p90_latency_ms} ms`;

            document.getElementById('tblP99Before').innerText = `${data.before_unindexed.p99_latency_ms} ms`;
            document.getElementById('tblP99After').innerText = `${data.after_indexed.p99_latency_ms} ms`;

            if (typeof showToast === 'function') showToast(`Benchmark finished: ${data.speedup_multiplier}!`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Benchmark error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    runLoadBenchmark();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
