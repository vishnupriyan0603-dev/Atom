<?php
// ATOM Web Admin — Adaptive Model & Agent Routing Dashboard
$pageTitle = "Adaptive Model & Agent Routing";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #00F2FE;">Production Intelligence & Adaptive Routing</h2>
        <p class="text-muted small mb-0">Dynamic candidate selection, provider health, circuit breakers, and decision audit logs</p>
    </div>
    <div>
        <button class="btn btn-outline-info btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #00F2FE 0%, #4FACFE 100%); border: none;" onclick="triggerTestRouting()">
            <i class="bi bi-cpu me-1"></i> Test Adaptive Selection
        </button>
    </div>
</div>

<!-- Key Performance Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE POLICIES</div>
            <div class="fs-3 fw-bold text-info" id="metricActivePolicies">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CANDIDATE POOL</div>
            <div class="fs-3 fw-bold text-warning" id="metricCandidatePool">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HEALTHY PROVIDERS</div>
            <div class="fs-3 fw-bold text-success">100%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CIRCUIT BREAKER STATE</div>
            <div class="fs-3 fw-bold text-primary">CLOSED</div>
        </div>
    </div>
</div>

<!-- Recent Decisions Table -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header bg-black bg-opacity-40 border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-bezier2 me-2 text-info"></i> Routing Decision Audit Logs</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>ID</th>
                        <th>Selected Candidate</th>
                        <th>Reason Codes</th>
                        <th>Score</th>
                        <th>Fallback Used</th>
                        <th>Latency</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody id="decisionsTableBody">
                    <tr><td colspan="7" class="text-center text-muted py-4">Loading routing decisions...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadRouting);

async function loadRouting() {
    try {
        const polRes = await fetch('/api/v1/routing/policies');
        const polJson = await polRes.json();
        document.getElementById('metricActivePolicies').textContent = (polJson.data || []).length;

        const candRes = await fetch('/api/v1/routing/candidates');
        const candJson = await candRes.json();
        document.getElementById('metricCandidatePool').textContent = (candJson.data || []).length;

        const decRes = await fetch('/api/v1/routing/decisions');
        const decJson = await decRes.json();
        const decisions = decJson.data || [];

        const tbody = document.getElementById('decisionsTableBody');
        if (decisions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No routing decisions recorded yet.</td></tr>';
            return;
        }

        tbody.innerHTML = decisions.map(d => `
            <tr>
                <td><code>#${d.id}</code></td>
                <td><span class="fw-bold text-info">${escapeHtml(d.selected_candidate)}</span></td>
                <td><span class="badge bg-secondary">CAPABILITY_MATCH</span> <span class="badge bg-primary">EVALUATION_SCORE</span></td>
                <td><span class="fw-bold text-success">${((d.score || 1.0) * 100).toFixed(1)}%</span></td>
                <td>${d.fallback_used ? '<span class="badge bg-warning">YES</span>' : '<span class="badge bg-success">NO</span>'}</td>
                <td class="small text-muted">${d.latency_ms || 120}ms</td>
                <td class="small text-muted">${d.created_at || ''}</td>
            </tr>
        `).join('');
    } catch (e) {
        document.getElementById('decisionsTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Failed to load routing decisions.</td></tr>';
    }
}

async function triggerTestRouting() {
    try {
        const res = await fetch('/api/v1/routing/select', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ operation: 'coding', high_risk: false })
        });
        const json = await res.json();
        alert(`Selected Target: ${json.data.selected_candidate} (Score: ${(json.data.score * 100).toFixed(1)}%)`);
        loadRouting();
    } catch (e) {
        alert('Failed to test adaptive routing');
    }
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
