<?php
// ATOM Web Admin — Phase 79: Real-Time Dynamic Database Connection Pool Governor & Leak Watchdog
$pageTitle = "Connection Pool Governor (Phase 79)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">Database Connection Pool Governor</h2>
        <p class="text-muted small mb-0">Phase 79: Multi-Tenant Connection Pool Slicing, Starvation Protection &amp; Autonomous Leaked Handle Reclaiming</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="reclaimLeakedConnections()">
            <i class="bi bi-recycle me-1"></i> Reclaim Leaked Leases
        </button>
    </div>
</div>

<!-- Pool Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">POOL UTILIZATION</div>
            <div class="fs-4 fw-bold text-sky-400" id="metricUtilization">6.0% (HEALTHY)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE CONNECTIONS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricActive" style="color: #34D399;">3 / 50 ACTIVE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AVAILABLE HANDLES</div>
            <div class="fs-4 fw-bold text-info" id="metricAvailable">47 AVAILABLE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL RECLAIMED LEAKS</div>
            <div class="fs-4 fw-bold text-warning" id="metricReclaimed">0 RECLAIMED</div>
        </div>
    </div>
</div>

<!-- Active Connection Leases Table & Lease Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-hdd-stack-fill me-2 text-sky-400"></i>Active Leased Connection Handles</span>
                <span class="badge bg-secondary" id="leasesBadge">3 LEASES</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Handle ID</th>
                                <th>Tenant</th>
                                <th>Context</th>
                                <th>Held For</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="leasesTableBody">
                            <tr><td colspan="5" class="text-center p-3 text-muted">Loading connection leases...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-info"><i class="bi bi-plus-circle-fill me-2"></i>Lease Connection Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TENANT ID</label>
                    <input type="text" id="leaseTenantInput" class="form-control bg-black text-white border-secondary small" value="tenant_gamma">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CONTEXT / QUERY OP</label>
                    <input type="text" id="leaseContextInput" class="form-control bg-black text-white border-secondary small" value="bulk_batch_import">
                </div>

                <button class="btn btn-sm btn-info text-dark fw-bold w-100 mb-3" onclick="leaseConnectionDemo()">
                    <i class="bi bi-key-fill me-1"></i> Request Connection Handle
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted">
                    <i class="bi bi-shield-lock-fill text-sky-400 me-1"></i> Handles held longer than 3.0 seconds are automatically reclaimed by the watchdog to prevent starvation.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadPoolStatus() {
    try {
        const res = await apiFetch('/database/pool/status');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricUtilization').innerText = `${d.utilization_pct}% (${d.active_connections}/${d.max_connections})`;
            document.getElementById('metricActive').innerText = `${d.active_connections} ACTIVE`;
            document.getElementById('metricAvailable').innerText = `${d.available_connections} AVAILABLE`;
            document.getElementById('metricReclaimed').innerText = `${d.total_reclaimed_leaks} RECLAIMED`;
            document.getElementById('leasesBadge').innerText = `${d.active_connections} LEASES`;

            renderLeasesTable(d.active_leases || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderLeasesTable(leases) {
    const tbody = document.getElementById('leasesTableBody');
    if (!leases || leases.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-3">No active connection leases.</td></tr>`;
        return;
    }

    tbody.innerHTML = leases.map(l => `
        <tr>
            <td class="fw-bold text-white font-monospace">${escapeHtml(l.handle_id)}</td>
            <td><span class="badge bg-secondary">${escapeHtml(l.tenant_id)}</span></td>
            <td class="text-muted text-xs">${escapeHtml(l.context)}</td>
            <td><span class="${l.is_leaked ? 'text-danger fw-bold' : 'text-emerald-400'}">${l.held_duration_s}s ${l.is_leaked ? '(LEAK)' : ''}</span></td>
            <td>
                <button class="btn btn-xs btn-outline-danger" onclick="releaseHandle('${escapeHtml(l.handle_id)}')">Release</button>
            </td>
        </tr>
    `).join('');
}

async function leaseConnectionDemo() {
    const tenant = document.getElementById('leaseTenantInput').value.trim();
    const ctx = document.getElementById('leaseContextInput').value.trim();

    try {
        const res = await apiFetch('/database/pool/lease', {
            method: 'POST',
            body: JSON.stringify({ tenant_id: tenant, context: ctx })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Leased connection handle: ${res.data.handle_id}`, 'success');
            loadPoolStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Lease error: ' + e.message, 'error');
    }
}

async function releaseHandle(handleId) {
    try {
        const res = await apiFetch('/database/pool/release', {
            method: 'POST',
            body: JSON.stringify({ handle_id: handleId })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Released handle: ${handleId}`, 'info');
            loadPoolStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Release error: ' + e.message, 'error');
    }
}

async function reclaimLeakedConnections() {
    try {
        const res = await apiFetch('/database/pool/reclaim-leaks', { method: 'POST' });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Reclaimed ${res.data.reclaimed_count} leaked handles`, 'success');
            loadPoolStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Reclaim error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadPoolStatus();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
