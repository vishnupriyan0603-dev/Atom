<?php
// ATOM Web Admin — Agent Evaluation & Continuous Improvement Dashboard
$pageTitle = "Agent Evaluation & Continuous Improvement";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #00F2FE;">Agent Evaluation & Continuous Improvement</h2>
        <p class="text-muted small mb-0">Benchmark datasets, sandbox evaluation runs, regression detection, and promotion policy control</p>
    </div>
    <div>
        <button class="btn btn-outline-info btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #00F2FE 0%, #4FACFE 100%); border: none;" data-bs-toggle="modal" data-bs-target="#newRunModal">
            <i class="bi bi-play-circle me-1"></i> Launch Evaluation Run
        </button>
    </div>
</div>

<!-- Evaluation Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL DATASETS</div>
            <div class="fs-3 fw-bold text-info" id="metricTotalDatasets">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EVALUATION RUNS</div>
            <div class="fs-3 fw-bold text-warning" id="metricTotalRuns">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AVG ACCURACY</div>
            <div class="fs-3 fw-bold text-success" id="metricAvgAccuracy">95.0%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">REGRESSIONS BLOCKED</div>
            <div class="fs-3 fw-bold text-primary" id="metricRegressionsBlocked">0</div>
        </div>
    </div>
</div>

<!-- Evaluation Runs Table -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header bg-black bg-opacity-40 border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-speedometer2 me-2 text-info"></i> Benchmark Evaluation Runs</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>ID</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>Total Cases</th>
                        <th>Aggregate Score</th>
                        <th>Completed At</th>
                    </tr>
                </thead>
                <tbody id="evalRunsTableBody">
                    <tr><td colspan="6" class="text-center text-muted py-4">Loading evaluation runs...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Launch Evaluation Run -->
<div class="modal fade" id="newRunModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-info"><i class="bi bi-speedometer2 me-2"></i> Launch Evaluation Run</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Target Subsystem</label>
                    <select class="form-select bg-black text-white border-secondary" id="evalTargetSelect">
                        <option value="agent">Controlled Agent Engine</option>
                        <option value="workflow">Autonomous Workflow Engine</option>
                        <option value="swarm">Multi-Agent Swarm Engine</option>
                        <option value="model">Model Gateway Driver</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="submitEvalRun()">Start Run</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadEvaluations);

async function loadEvaluations() {
    try {
        const dsRes = await fetch('/api/v1/evaluations/datasets');
        const dsJson = await dsRes.json();
        const datasets = dsJson.data || [];
        document.getElementById('metricTotalDatasets').textContent = datasets.length;

        const runRes = await fetch('/api/v1/evaluations/runs');
        const runJson = await runRes.json();
        const runs = runJson.data || [];
        document.getElementById('metricTotalRuns').textContent = runs.length;

        const tbody = document.getElementById('evalRunsTableBody');
        if (runs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No evaluation runs recorded yet.</td></tr>';
            return;
        }

        tbody.innerHTML = runs.map(r => `
            <tr>
                <td><code>#${r.id}</code></td>
                <td><span class="fw-bold">${escapeHtml(r.target_type)}</span> <code>#${escapeHtml(r.target_id)}</code></td>
                <td><span class="badge bg-success">${(r.status || 'COMPLETED').toUpperCase()}</span></td>
                <td>${r.total_cases || 0}</td>
                <td><span class="text-info fw-bold">${((r.aggregate_score || 1.0) * 100).toFixed(1)}%</span></td>
                <td class="small text-muted">${r.completed_at || ''}</td>
            </tr>
        `).join('');
    } catch (e) {
        document.getElementById('evalRunsTableBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load evaluations.</td></tr>';
    }
}

async function submitEvalRun() {
    const targetType = document.getElementById('evalTargetSelect').value;
    try {
        await fetch('/api/v1/evaluations/runs', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ dataset_id: 1, target_type: targetType, target_id: '1' })
        });
        bootstrap.Modal.getInstance(document.getElementById('newRunModal')).hide();
        loadEvaluations();
    } catch (e) {
        alert('Failed to launch evaluation run');
    }
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
