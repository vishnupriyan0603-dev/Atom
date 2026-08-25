<?php
// ATOM Web Admin — Phase 67: Autonomous API Latency Heatmap & SLA-Violation Alert Mesh
$pageTitle = "Latency Heatmap (Phase 67)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #34D399;">API Latency Heatmap &amp; SLA Alert Mesh</h2>
        <p class="text-muted small mb-0">Phase 67: Sub-Millisecond Latency Bins ($P0, P1, P2, P3$), Multi-Subsystem Heatmap Grid &amp; Real-Time SLA Breach Alarms</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-emerald text-white fw-bold" style="background: #10B981;" onclick="simulateTrafficBurst()">
            <i class="bi bi-speedometer2 me-1"></i> Simulate Latency Traffic
        </button>
    </div>
</div>

<!-- SLA Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SLA COMPLIANCE</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricSla" style="color: #34D399;">100.0% (HONORED)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">P0 FAST REQUESTS (&lt;10ms)</div>
            <div class="fs-4 fw-bold text-success" id="metricP0">7 REQUESTS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SLA THRESHOLD</div>
            <div class="fs-4 fw-bold text-info">&le; 50.0 ms SLO</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SLA BREACHES</div>
            <div class="fs-4 fw-bold text-danger" id="metricBreaches">0 BREACHES</div>
        </div>
    </div>
</div>

<!-- Subsystems Latency Heatmap Grid -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6"><i class="bi bi-grid-3x3-gap-fill me-2 text-emerald-400"></i>Subsystems Response Latency Matrix</span>
        <span class="badge bg-success" id="statusBadge">ALL SYSTEMS OPTIMAL</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle small">
                <thead class="table-secondary text-uppercase text-muted">
                    <tr>
                        <th>Subsystem</th>
                        <th>Reqs</th>
                        <th>Avg Latency</th>
                        <th>Min Latency</th>
                        <th>Max Latency</th>
                        <th>SLA Status</th>
                    </tr>
                </thead>
                <tbody id="heatmapTableBody">
                    <tr><td colspan="6" class="text-center p-3 text-muted">Loading latency heatmap...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadHeatmap() {
    try {
        const res = await apiFetch('/telemetry/heatmap/matrix');
        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricSla').innerText = `${data.sla_compliance_pct}% (${data.sla_compliance_pct >= 99 ? 'HONORED' : 'WARNING'})`;
            document.getElementById('metricP0').innerText = `${data.buckets.p0_fast} REQUESTS`;
            document.getElementById('metricBreaches').innerText = `${data.buckets.p3_breach} BREACHES`;

            renderTable(data.matrix || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderTable(matrix) {
    const tbody = document.getElementById('heatmapTableBody');
    if (!matrix || matrix.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted">No latency data recorded yet.</td></tr>`;
        return;
    }

    tbody.innerHTML = matrix.map(m => {
        const isOptimal = m.status === 'OPTIMAL';
        const badgeClass = isOptimal ? 'bg-success' : (m.status === 'WARNING' ? 'bg-warning text-dark' : 'bg-danger');

        return `
            <tr>
                <td class="fw-bold text-white"><i class="bi bi-cpu text-emerald-400 me-2"></i>${escapeHtml(m.subsystem)}</td>
                <td>${m.requests_count}</td>
                <td class="text-emerald-400 fw-bold">${m.avg_ms} ms</td>
                <td class="text-muted">${m.min_ms} ms</td>
                <td class="text-muted">${m.max_ms} ms</td>
                <td><span class="badge ${badgeClass}">${m.status}</span></td>
            </tr>
        `;
    }).join('');
}

async function simulateTrafficBurst() {
    const subsystems = ['GatewayCrossbar', 'RateLimiter', 'VoiceHarmonizer', 'PostQuantumVault', 'QueryOptimizer'];
    try {
        for (let i = 0; i < 5; i++) {
            const sub = subsystems[Math.floor(Math.random() * subsystems.length)];
            const dur = (Math.random() * 12).toFixed(2);
            await apiFetch('/telemetry/heatmap/record', {
                method: 'POST',
                body: JSON.stringify({ subsystem: sub, duration_ms: parseFloat(dur) })
            });
        }
        if (typeof showToast === 'function') showToast('Simulated 5 real-time latency bursts!', 'success');
        loadHeatmap();
    } catch (e) {
        if (typeof showToast === 'function') showToast('Traffic simulation error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadHeatmap();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
