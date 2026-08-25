<?php
// ATOM Web Admin — Phase 51: Autonomous AST Performance Profiler & Algorithmic Complexity Studio
$pageTitle = "Performance Profiler & Complexity (Phase 51)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F59E0B;">Autonomous AST Performance Profiler &amp; Complexity Studio</h2>
        <p class="text-muted small mb-0">Phase 51: Big-O Time/Space Complexity Analysis ($O(N^2), O(N)$), Nested Loop Optimization, N+1 Query Elimination &amp; Memory Leak Detector</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-warning text-dark fw-bold" onclick="runProfilerDemo()">
            <i class="bi bi-speedometer2 me-1"></i> Run Profiler Demo
        </button>
    </div>
</div>

<!-- Performance Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ALGORITHMIC COMPLEXITY</div>
            <div class="fs-4 fw-bold text-danger" id="metricComplexity">O(N²) DETECTED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PERFORMANCE SCORE</div>
            <div class="fs-4 fw-bold text-warning" id="metricPerfScore">60.0 / 100</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DETECTED BOTTLENECKS</div>
            <div class="fs-4 fw-bold text-danger" id="metricBottleneckCount">1 NESTED LOOP</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MEMORY LEAKS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricLeakCount" style="color: #34D399;">0 UNCLOSED HANDLES</div>
        </div>
    </div>
</div>

<!-- Main Section: Code Input & Optimization Output -->
<div class="row g-4 mb-4">
    
    <!-- 1. Source Code Input -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-warning"><i class="bi bi-code-slash me-2"></i>Source Code Under Analysis</span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-xs btn-outline-secondary" onclick="loadSampleNestedLoop()">Nested Loop $O(N^2)$</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="loadSampleNPlusOne()">N+1 Query</button>
                </div>
            </div>
            <div class="card-body">
                <textarea id="profilerCodeInput" class="form-control bg-black text-white border-secondary small mb-3" rows="12" style="font-family: monospace; font-size: 12px;"><?php
function findMatchingOrders($orders, $transactions) {
    $matches = [];
    foreach ($orders as $order) {
        foreach ($transactions as $txn) {
            if ($order['id'] === $txn['id']) {
                $matches[] = $order;
            }
        }
    }
    return $matches;
}
?></textarea>

                <div class="d-flex gap-2">
                    <button class="btn btn-warning text-dark fw-bold flex-grow-1" onclick="analyzeCodePerformance()">
                        <i class="bi bi-search me-1"></i> Analyze Big-O Complexity
                    </button>
                    <button class="btn btn-success fw-bold flex-grow-1" onclick="synthesizeOptimizationPatch()">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Synthesize O(N) Patch
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Optimization Output & Findings -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-emerald-400" style="color: #34D399;"><i class="bi bi-cpu-fill me-2"></i>Optimized $O(N)$ Code &amp; Profiling Report</span>
                <button class="btn btn-xs btn-outline-secondary" onclick="copyOptimizedCode()"><i class="bi bi-clipboard me-1"></i>Copy</button>
            </div>
            <div class="card-body">
                <div id="bottlenecksListContainer" class="p-2 mb-3 bg-black border border-secondary rounded text-xs space-y-1" style="min-height: 50px;">
                    Click 'Analyze Big-O Complexity' to inspect algorithmic performance.
                </div>

                <textarea id="optimizedCodeOutput" class="form-control bg-black text-emerald-400 border-secondary small" rows="12" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>// Optimized O(N) hash map code will appear here...</textarea>
            </div>
        </div>
    </div>
</div>

<script>
async function analyzeCodePerformance() {
    const code = document.getElementById('profilerCodeInput').value;
    try {
        const res = await apiFetch('/profiler/analyze', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });

        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricComplexity').innerText = data.complexity;
            document.getElementById('metricComplexity').className = `fs-4 fw-bold text-${data.complexity === 'O(1)' || data.complexity === 'O(N)' ? 'success' : 'danger'}`;
            document.getElementById('metricPerfScore').innerText = `${data.performance_score} / 100`;
            document.getElementById('metricBottleneckCount').innerText = `${data.bottlenecks_count} BOTTLENECKS`;
            document.getElementById('metricLeakCount').innerText = `${data.memory_leaks_count} LEAKS`;

            const container = document.getElementById('bottlenecksListContainer');
            if (data.bottlenecks.length > 0 || data.memory_leaks.length > 0) {
                let html = '';
                data.bottlenecks.forEach(b => {
                    html += `<div class="p-1.5 rounded bg-black border border-danger mb-1"><span class="badge bg-danger">${b.complexity}</span> <span class="text-white fw-bold">${escapeHtml(b.type)}:</span> <span class="text-muted">${escapeHtml(b.impact)}</span></div>`;
                });
                data.memory_leaks.forEach(l => {
                    html += `<div class="p-1.5 rounded bg-black border border-warning mb-1"><span class="badge bg-warning text-dark">LEAK</span> <span class="text-white fw-bold">${escapeHtml(l.type)}:</span> <span class="text-muted">${escapeHtml(l.remediation)}</span></div>`;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-success fw-bold">✅ Algorithmic complexity is optimal! No memory leaks found.</div>';
            }

            if (typeof showToast === 'function') showToast(`Analysis complete: Complexity ${data.complexity}`, 'info');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Analysis error: ' + e.message, 'error');
    }
}

async function synthesizeOptimizationPatch() {
    const code = document.getElementById('profilerCodeInput').value;
    try {
        const res = await apiFetch('/profiler/optimize', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });

        if (res && res.success) {
            document.getElementById('optimizedCodeOutput').value = res.data.optimized_code;
            if (typeof showToast === 'function') showToast(`Successfully optimized complexity to ${res.data.optimized_complexity}!`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Optimization error: ' + e.message, 'error');
    }
}

function loadSampleNestedLoop() {
    document.getElementById('profilerCodeInput').value = `<?php
function findMatchingOrders($orders, $transactions) {
    $matches = [];
    foreach ($orders as $order) {
        foreach ($transactions as $txn) {
            if ($order['id'] === $txn['id']) {
                $matches[] = $order;
            }
        }
    }
    return $matches;
}
?>`;
    analyzeCodePerformance();
}

function loadSampleNPlusOne() {
    document.getElementById('profilerCodeInput').value = `<?php
function fetchUserOrders($userIds, $db) {
    $results = [];
    foreach ($userIds as $uid) {
        $orders = $db->query("SELECT * FROM orders WHERE user_id = " . $uid);
        $results[] = $orders;
    }
    return $results;
}
?>`;
    analyzeCodePerformance();
}

function copyOptimizedCode() {
    navigator.clipboard.writeText(document.getElementById('optimizedCodeOutput').value);
    if (typeof showToast === 'function') showToast('Optimized code copied!', 'info');
}

function runProfilerDemo() {
    loadSampleNestedLoop();
    synthesizeOptimizationPatch();
}

document.addEventListener('DOMContentLoaded', () => {
    runProfilerDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
