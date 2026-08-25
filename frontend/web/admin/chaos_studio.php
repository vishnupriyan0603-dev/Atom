<?php
// ATOM Web Admin — Phase 81: Autonomous Chaos Engineering & Failure Injection Mesh
$pageTitle = "Chaos Engineering (Phase 81)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F97316;">Autonomous Chaos Engineering &amp; Fault Injector</h2>
        <p class="text-muted small mb-0">Phase 81: Multi-Vector Failure Injection (Synthetic Latency, HTTP 500 Faults, Memory Exhaustion) &amp; Automated Blast Radius Governors</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-danger text-white fw-bold" onclick="emergencyStopAllChaos()">
            <i class="bi bi-stop-circle-fill me-1"></i> EMERGENCY ABORT ALL
        </button>
    </div>
</div>

<!-- Chaos Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE EXPERIMENTS</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricActiveCount" style="color: #F59E0B;">1 RUNNING</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">BLAST RADIUS LIMIT</div>
            <div class="fs-4 fw-bold text-orange-400" style="color: #F97316;">MAX 25% TRAFFIC</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EMERGENCY KILL-SWITCH</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricEmergency" style="color: #34D399;">ARMED &amp; SAFE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">FAULT RESILIENCE</div>
            <div class="fs-4 fw-bold text-info">Zero-Downtime</div>
        </div>
    </div>
</div>

<!-- Active Chaos Experiments Table & Experiment Launcher Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-radioactive text-warning me-2"></i>Active Chaos Experiments</span>
                <span class="badge bg-secondary" id="expBadge">1 EXPERIMENTS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Experiment ID</th>
                                <th>Fault Vector</th>
                                <th>Blast Radius</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="expTableBody">
                            <tr><td colspan="5" class="text-center p-3 text-muted">Loading experiments...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-warning"><i class="bi bi-play-btn-fill me-2"></i>Launch Controlled Experiment</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">FAULT INJECTION VECTOR</label>
                    <select id="faultSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="latency">Synthetic Upstream Latency (+500ms)</option>
                        <option value="http_500_error">Simulated HTTP 500 Server Faults</option>
                        <option value="memory_pressure">Memory Allocation Pressure</option>
                        <option value="packet_loss">Network Packet Drop (WebRTC)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>BLAST RADIUS PERCENTAGE</span>
                        <span class="text-warning fw-bold" id="blastLabel">10%</span>
                    </label>
                    <input type="range" class="form-range" min="1" max="25" value="10" id="blastSlider" oninput="document.getElementById('blastLabel').innerText = this.value + '%'">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TARGET ROUTE / ENDPOINT</label>
                    <input type="text" id="targetRouteInput" class="form-control bg-black text-white border-secondary small" value="/api/users/profile">
                </div>

                <button class="btn btn-sm btn-warning text-dark fw-bold w-100 mb-3" onclick="launchChaosExperiment()">
                    <i class="bi bi-lightning-fill me-1"></i> Start Chaos Experiment
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted">
                    <i class="bi bi-shield-check text-warning me-1"></i> Blast radius is strictly capped at max 25% traffic to prevent production outages.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadExperiments() {
    try {
        const res = await apiFetch('/infrastructure/chaos/experiments');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricActiveCount').innerText = `${d.active_count} RUNNING`;
            document.getElementById('metricEmergency').innerText = d.emergency_stop_engaged ? 'TRIPPED (STOPPED)' : 'ARMED & SAFE';
            document.getElementById('metricEmergency').className = `fs-4 fw-bold ${d.emergency_stop_engaged ? 'text-danger' : 'text-emerald-400'}`;
            document.getElementById('expBadge').innerText = `${d.experiments.length} EXPERIMENTS`;

            renderExperimentsTable(d.experiments || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderExperimentsTable(experiments) {
    const tbody = document.getElementById('expTableBody');
    if (!experiments || experiments.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-3">No chaos experiments running.</td></tr>`;
        return;
    }

    tbody.innerHTML = experiments.map(exp => `
        <tr>
            <td class="fw-bold text-white">${escapeHtml(exp.experiment_id)}</td>
            <td><span class="badge bg-dark border border-warning text-warning">${escapeHtml(exp.fault_type)}</span></td>
            <td class="text-orange-400 fw-bold">${exp.blast_radius_pct}%</td>
            <td><span class="badge ${exp.status === 'RUNNING' ? 'bg-success' : 'bg-danger'}">${escapeHtml(exp.status)}</span></td>
            <td>
                ${exp.status === 'RUNNING' ? `<button class="btn btn-xs btn-outline-danger" onclick="stopSingleExp('${escapeHtml(exp.experiment_id)}')">Stop</button>` : `<span class="text-muted text-xs">Stopped</span>`}
            </td>
        </tr>
    `).join('');
}

async function launchChaosExperiment() {
    const fault = document.getElementById('faultSelect').value;
    const blast = parseInt(document.getElementById('blastSlider').value, 10);
    const target = document.getElementById('targetRouteInput').value.trim();
    const id = 'exp_' + fault + '_' + Math.random().toString(36).substring(7);

    try {
        const res = await apiFetch('/infrastructure/chaos/start', {
            method: 'POST',
            body: JSON.stringify({
                experiment_id: id,
                fault_type: fault,
                blast_radius_pct: blast,
                targets: target ? [target] : []
            })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Chaos experiment '${id}' launched (${blast}% blast)`, 'warning');
            loadExperiments();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Chaos error: ' + e.message, 'error');
    }
}

async function stopSingleExp(id) {
    try {
        const res = await apiFetch('/infrastructure/chaos/stop', {
            method: 'POST',
            body: JSON.stringify({ experiment_id: id })
        });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Experiment ${id} stopped`, 'info');
            loadExperiments();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Stop error: ' + e.message, 'error');
    }
}

async function emergencyStopAllChaos() {
    try {
        const res = await apiFetch('/infrastructure/chaos/stop', { method: 'POST', body: JSON.stringify({}) });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast('EMERGENCY ABORT: All chaos experiments stopped!', 'danger');
            loadExperiments();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Abort error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadExperiments();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
