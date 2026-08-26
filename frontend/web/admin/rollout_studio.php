<?php
// ATOM Web Admin — Phase 95: Real-Time Dynamic Feature Flag Rollout Engine & Multi-Variant AB Testing Splitter
$pageTitle = "Feature Flag Rollout & A/B Testing (Phase 95)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #10B981;">Feature Flag Rollouts &amp; A/B Splitter</h2>
        <p class="text-muted small mb-0">Phase 95: Percentage Rollouts (0-100%), Deterministic User Bucketing (CRC32), Role Targeting &amp; Emergency Kill-Switch</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-success text-dark fw-bold" onclick="evaluateFlagDemo()">
            <i class="bi bi-person-check-fill me-1"></i> Evaluate User
        </button>
    </div>
</div>

<!-- Rollout Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE FLAGS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricFlagsCount" style="color: #34D399;">3 FLAGS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">BUCKETING ALGORITHM</div>
            <div class="fs-4 fw-bold text-cyan-400">Deterministic CRC32</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">KILL-SWITCH SYSTEM</div>
            <div class="fs-4 fw-bold text-info">Sub-ms Interlock</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TARGETING MODES</div>
            <div class="fs-4 fw-bold text-pink-400">Role &amp; Percentage</div>
        </div>
    </div>
</div>

<!-- Flags Table & User Evaluation Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-toggles text-emerald-400 me-2"></i>Feature Flags Matrix</span>
                <span class="badge bg-secondary" id="flagsBadge">3 FLAGS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Flag Key</th>
                                <th>Rollout %</th>
                                <th>Targeting</th>
                                <th>Status</th>
                                <th>Kill-Switch</th>
                            </tr>
                        </thead>
                        <tbody id="flagsTableBody">
                            <tr><td colspan="5" class="text-center p-3 text-muted">Loading flags...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-emerald-400"><i class="bi bi-person-gear me-2"></i>User Evaluation Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SELECT FEATURE FLAG</label>
                    <select id="flagSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="quantum_encryption_handshake" selected>quantum_encryption_handshake (50%)</option>
                        <option value="ai_streaming_fast_mode">ai_streaming_fast_mode (100%)</option>
                        <option value="legacy_soap_fallback">legacy_soap_fallback (0% - Disabled)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USER IDENTIFIER</label>
                    <input type="text" id="userIdInput" class="form-control bg-black text-white border-secondary small" value="user_guest_123">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USER ROLE</label>
                    <select id="roleSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="guest" selected>guest</option>
                        <option value="beta_tester">beta_tester (Targeted Override)</option>
                        <option value="admin">admin (Targeted Override)</option>
                    </select>
                </div>

                <button class="btn btn-sm btn-success text-dark fw-bold w-100 mb-3" onclick="evaluateFlagDemo()">
                    <i class="bi bi-check2-circle me-1"></i> Evaluate Flag for Context
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="evalResultBox">
                    [Ready] Click 'Evaluate Flag for Context' to test deterministic partitioning...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadFlags() {
    try {
        const res = await apiFetch('/infrastructure/flags/list');
        if (res && res.success) {
            const list = res.data || [];
            document.getElementById('metricFlagsCount').innerText = `${list.length} FLAGS`;
            document.getElementById('flagsBadge').innerText = `${list.length} FLAGS`;

            renderFlagsTable(list);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderFlagsTable(flags) {
    const tbody = document.getElementById('flagsTableBody');
    if (!flags || flags.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-3">No flags registered.</td></tr>`;
        return;
    }

    tbody.innerHTML = flags.map(f => `
        <tr>
            <td class="fw-bold text-white font-monospace"><i class="bi bi-flag-fill text-emerald-400 me-2"></i>${escapeHtml(f.flag_key)}</td>
            <td><span class="text-warning fw-bold">${f.rollout_pct}%</span></td>
            <td><span class="text-muted text-xs">${f.allowed_roles.length ? f.allowed_roles.join(', ') : 'All Users'}</span></td>
            <td><span class="badge ${f.enabled ? 'bg-success' : 'bg-danger'}">${f.enabled ? 'ENABLED' : 'DISABLED'}</span></td>
            <td>
                <button class="btn btn-xs ${f.enabled ? 'btn-outline-danger' : 'btn-outline-success'}" onclick="toggleFlagState('${escapeHtml(f.flag_key)}', ${!f.enabled})">
                    ${f.enabled ? 'Kill' : 'Enable'}
                </button>
            </td>
        </tr>
    `).join('');
}

async function toggleFlagState(key, enabled) {
    try {
        const res = await apiFetch('/infrastructure/flags/toggle', {
            method: 'POST',
            body: JSON.stringify({ flag_key: key, enabled: enabled })
        });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Flag ${key} set to ${enabled ? 'ENABLED' : 'DISABLED'}`, 'info');
            loadFlags();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Toggle error: ' + e.message, 'error');
    }
}

async function evaluateFlagDemo() {
    const key = document.getElementById('flagSelect').value;
    const user = document.getElementById('userIdInput').value.trim();
    const role = document.getElementById('roleSelect').value;

    try {
        const res = await apiFetch('/infrastructure/flags/evaluate', {
            method: 'POST',
            body: JSON.stringify({
                flag_key: key,
                user_id: user,
                attributes: { role: role }
            })
        });

        if (res && res.success) {
            const d = res.data;
            const statusColor = d.enabled ? 'text-emerald-400' : 'text-danger';

            document.getElementById('evalResultBox').innerHTML = `
                <div class="${statusColor} fw-bold mb-1">[${d.enabled ? 'ENABLED' : 'DISABLED'}] Variant: '${d.variant}'</div>
                <div class="text-white text-xs mb-1"><strong>Reason:</strong> ${escapeHtml(d.reason)}</div>
                <div class="text-muted text-xs">User Bucket: ${d.bucket !== undefined ? d.bucket + '/100' : 'N/A'}</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Flag evaluated: ${d.enabled ? 'ENABLED' : 'DISABLED'} (${d.variant})`, d.enabled ? 'success' : 'warning');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Eval error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadFlags();
    evaluateFlagDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
