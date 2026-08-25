<?php
// ATOM Web Admin — Phase 85: Real-Time Dynamic Circuit Breaker & Fallback Mesh Governor
$pageTitle = "Circuit Breaker Mesh (Phase 85)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EAB308;">Dynamic Circuit Breaker &amp; Fallback Mesh</h2>
        <p class="text-muted small mb-0">Phase 85: Tri-State Finite State Machine (CLOSED, OPEN, HALF_OPEN), Sliding-Window Error Thresholds &amp; Fast-Fail Fallbacks</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-warning text-dark fw-bold" onclick="testCircuitExecution(false)">
            <i class="bi bi-lightning-charge-fill me-1"></i> Send Healthy Probe
        </button>
    </div>
</div>

<!-- Circuit Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">REGISTERED CIRCUITS</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricCircuitsCount" style="color: #FACC15;">3 SERVICES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HEALTHY CIRCUITS (CLOSED)</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricHealthyCount" style="color: #34D399;">3 CLOSED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TRIPPED CIRCUITS (OPEN)</div>
            <div class="fs-4 fw-bold text-danger" id="metricTrippedCount">0 OPEN</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PROBE TRIAL (HALF_OPEN)</div>
            <div class="fs-4 fw-bold text-info" id="metricHalfOpenCount">0 HALF_OPEN</div>
        </div>
    </div>
</div>

<!-- Circuit Breaker Matrix & Execution Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-toggle2-on text-warning me-2"></i>Monitored Service Circuits</span>
                <span class="badge bg-secondary" id="circuitsBadge">3 SERVICES</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Service</th>
                                <th>State</th>
                                <th>Error Rate</th>
                                <th>Requests</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="circuitsTableBody">
                            <tr><td colspan="5" class="text-center p-3 text-muted">Loading circuits...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-warning"><i class="bi bi-play-circle-fill me-2"></i>Fault Simulation Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TARGET SERVICE</label>
                    <select id="targetServiceSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="payment_gateway_api">payment_gateway_api (40% threshold)</option>
                        <option value="upstream_weather_service">upstream_weather_service (50% threshold)</option>
                        <option value="external_sms_provider">external_sms_provider (30% threshold)</option>
                    </select>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-sm btn-success text-dark fw-bold flex-grow-1" onclick="testCircuitExecution(false)">
                        <i class="bi bi-check-circle me-1"></i> Success (200)
                    </button>
                    <button class="btn btn-sm btn-danger text-white fw-bold flex-grow-1" onclick="testCircuitExecution(true)">
                        <i class="bi bi-x-circle me-1"></i> Fault (500)
                    </button>
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="circuitResultBox">
                    [Ready] Trigger calls to test automatic circuit tripping &amp; fallback delivery...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadCircuits() {
    try {
        const res = await apiFetch('/infrastructure/circuit/services');
        if (res && res.success) {
            const list = res.data || [];
            let closed = 0, open = 0, halfOpen = 0;

            list.forEach(c => {
                if (c.state === 'CLOSED') closed++;
                else if (c.state === 'OPEN') open++;
                else if (c.state === 'HALF_OPEN') halfOpen++;
            });

            document.getElementById('metricCircuitsCount').innerText = `${list.length} SERVICES`;
            document.getElementById('metricHealthyCount').innerText = `${closed} CLOSED`;
            document.getElementById('metricTrippedCount').innerText = `${open} OPEN`;
            document.getElementById('metricHalfOpenCount').innerText = `${halfOpen} HALF_OPEN`;
            document.getElementById('circuitsBadge').innerText = `${list.length} SERVICES`;

            renderCircuitsTable(list);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderCircuitsTable(circuits) {
    const tbody = document.getElementById('circuitsTableBody');
    if (!circuits || circuits.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-3">No circuits registered.</td></tr>`;
        return;
    }

    tbody.innerHTML = circuits.map(c => {
        let badgeClass = 'bg-success';
        if (c.state === 'OPEN') badgeClass = 'bg-danger';
        else if (c.state === 'HALF_OPEN') badgeClass = 'bg-warning text-dark';

        return `
            <tr>
                <td class="fw-bold text-white"><i class="bi bi-hdd-network me-2 text-warning"></i>${escapeHtml(c.service_name)}</td>
                <td><span class="badge ${badgeClass}">${escapeHtml(c.state)}</span></td>
                <td><span class="${c.error_rate_pct >= c.failure_threshold_pct ? 'text-danger fw-bold' : 'text-muted'}">${c.error_rate_pct}% (${c.failed_requests}/${c.total_requests})</span></td>
                <td class="text-muted text-xs">${c.consecutive_failures} cons. fails</td>
                <td>
                    ${c.state === 'OPEN' ? `<button class="btn btn-xs btn-outline-success" onclick="overrideState('${escapeHtml(c.service_name)}', 'CLOSED')">Reset</button>` : `<button class="btn btn-xs btn-outline-danger" onclick="overrideState('${escapeHtml(c.service_name)}', 'OPEN')">Trip</button>`}
                </td>
            </tr>
        `;
    }).join('');
}

async function testCircuitExecution(fail) {
    const service = document.getElementById('targetServiceSelect').value;

    try {
        const res = await apiFetch('/infrastructure/circuit/execute', {
            method: 'POST',
            body: JSON.stringify({ service_name: service, simulate_failure: fail })
        });

        if (res && res.success) {
            const d = res.data;
            const statusClass = d.success ? 'text-emerald-400' : 'text-danger';
            document.getElementById('circuitResultBox').innerHTML = `
                <div class="${statusClass} fw-bold mb-1">[${d.circuit_state}] ${d.reason}</div>
                <div class="text-white"><strong>Output:</strong> ${JSON.stringify(d.result)}</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Circuit ${service}: [${d.circuit_state}] ${d.reason}`, d.success ? 'success' : 'warning');
            }
            loadCircuits();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Exec error: ' + e.message, 'error');
    }
}

async function overrideState(service, state) {
    try {
        const res = await apiFetch('/infrastructure/circuit/reset', {
            method: 'POST',
            body: JSON.stringify({ service_name: service, target_state: state })
        });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Service ${service} set to ${state}`, 'info');
            loadCircuits();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Reset error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadCircuits();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
