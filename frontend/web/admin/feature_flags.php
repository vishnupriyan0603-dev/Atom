<?php
// ATOM Web Admin — Phase 77: Real-Time Dynamic Feature Flag Matrix & Percentage Rollout Engine
$pageTitle = "Feature Flags (Phase 77)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #10B981;">Feature Flags &amp; Gradual Rollout Matrix</h2>
        <p class="text-muted small mb-0">Phase 77: Multi-Tenant Percentage-Based Rollout ($0\%\text{--}100\%$), Instant Kill-Switches &amp; User Whitelisting</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-success text-dark fw-bold" onclick="evaluateFlagDemo()">
            <i class="bi bi-lightning-charge-fill me-1"></i> Evaluate Demo Flag
        </button>
    </div>
</div>

<!-- Flags Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE FLAGS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricActiveFlags" style="color: #34D399;">3 ENABLED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DISABLED (KILL-SWITCH)</div>
            <div class="fs-4 fw-bold text-danger" id="metricDisabledFlags">1 DISABLED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PARTIAL ROLLOUTS</div>
            <div class="fs-4 fw-bold text-warning" id="metricPartialFlags">2 CANARY</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EVALUATION SPEED</div>
            <div class="fs-4 fw-bold text-info">&lt; 0.1 ms (In-Memory)</div>
        </div>
    </div>
</div>

<!-- Feature Flags Table & Evaluation Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-toggles me-2 text-emerald-400"></i>Configured Feature Flags</span>
                <span class="badge bg-secondary" id="flagsBadge">4 FLAGS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Flag Key</th>
                                <th>Status</th>
                                <th>Rollout %</th>
                                <th>Whitelists</th>
                            </tr>
                        </thead>
                        <tbody id="flagsTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Loading feature flags...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-emerald-400"><i class="bi bi-play-circle-fill me-2"></i>Evaluate Flag for User</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SELECT FLAG</label>
                    <select id="evalFlagSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="beta_voice_cloning">beta_voice_cloning (25%)</option>
                        <option value="post_quantum_v2">post_quantum_v2 (100%)</option>
                        <option value="legacy_xml_export">legacy_xml_export (0%)</option>
                        <option value="iot_telemetry_mesh">iot_telemetry_mesh (50%)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USER ID</label>
                    <input type="text" id="evalUserInput" class="form-control bg-black text-white border-secondary small" value="user_alex">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TENANT ID</label>
                    <input type="text" id="evalTenantInput" class="form-control bg-black text-white border-secondary small" value="tenant_vip">
                </div>

                <button class="btn btn-sm btn-success text-dark fw-bold w-100 mb-3" onclick="evaluateFlagDemo()">
                    <i class="bi bi-check2-circle me-1"></i> Check Flag State
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="evalResultBox">
                    [Ready] Click 'Check Flag State' to test runtime rule resolution...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadFeatureFlags() {
    try {
        const res = await apiFetch('/config/flags/list');
        if (res && res.success) {
            const flags = res.data || [];
            let active = 0, disabled = 0, partial = 0;

            flags.forEach(f => {
                if (!f.enabled || f.rollout_pct === 0) disabled++;
                else if (f.rollout_pct < 100) partial++;
                else active++;
            });

            document.getElementById('metricActiveFlags').innerText = `${active} FULL`;
            document.getElementById('metricDisabledFlags').innerText = `${disabled} OFF`;
            document.getElementById('metricPartialFlags').innerText = `${partial} CANARY`;
            document.getElementById('flagsBadge').innerText = `${flags.length} FLAGS`;

            renderFlagsTable(flags);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderFlagsTable(flags) {
    const tbody = document.getElementById('flagsTableBody');
    if (!flags || flags.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">No flags configured.</td></tr>`;
        return;
    }

    tbody.innerHTML = flags.map(f => `
        <tr>
            <td class="fw-bold text-white"><i class="bi bi-flag-fill text-emerald-400 me-1"></i>${escapeHtml(f.key)}</td>
            <td><span class="badge ${f.enabled ? 'bg-success' : 'bg-danger'}">${f.enabled ? 'ENABLED' : 'DISABLED'}</span></td>
            <td><span class="text-info fw-bold">${f.rollout_pct}%</span></td>
            <td class="text-muted text-xs">${(f.whitelist_tenants || []).concat(f.whitelist_users || []).join(', ') || 'None'}</td>
        </tr>
    `).join('');
}

async function evaluateFlagDemo() {
    const flag = document.getElementById('evalFlagSelect').value;
    const user = document.getElementById('evalUserInput').value.trim();
    const tenant = document.getElementById('evalTenantInput').value.trim();

    try {
        const res = await apiFetch('/config/flags/evaluate', {
            method: 'POST',
            body: JSON.stringify({ flag: flag, user_id: user, tenant_id: tenant })
        });

        if (res && res.success) {
            const d = res.data;
            const badge = d.is_active ? '<span class="text-emerald-400 fw-bold">[ACTIVE]</span>' : '<span class="text-danger fw-bold">[DISABLED]</span>';
            document.getElementById('evalResultBox').innerHTML = `
                ${badge} <strong>${escapeHtml(d.flag)}</strong><br>
                <span class="text-muted">Reason: ${escapeHtml(d.reason)}</span>
            `;

            if (typeof showToast === 'function') {
                showToast(`Flag ${d.flag}: ${d.is_active ? 'Active' : 'Disabled'}`, d.is_active ? 'success' : 'info');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Eval error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadFeatureFlags();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
