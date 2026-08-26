<?php
// ATOM Web Admin — Phase 97: Real-Time Dynamic Webhook Dead-Letter Queue (DLQ) Auto-Replay & Exponential Backoff Resiliency Crossbar
$pageTitle = "Webhook DLQ & Replay (Phase 97)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F43F5E;">Webhook Dead-Letter Queue &amp; Auto-Replay</h2>
        <p class="text-muted small mb-0">Phase 97: Exponential Backoff with Jitter ($2^{\text{attempt}} + \text{jitter}$), DLQ Quarantine &amp; Resiliency Replay Crossbar</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-danger fw-bold" onclick="replayDlqDemo()">
            <i class="bi bi-arrow-repeat me-1"></i> Replay Pending DLQ
        </button>
    </div>
</div>

<!-- DLQ Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL DLQ ITEMS</div>
            <div class="fs-4 fw-bold text-rose-400" id="metricTotalDlq" style="color: #FB7185;">2 ITEMS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PENDING RETRIES</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricPendingRetries" style="color: #FBBF24;">2 RETRIES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">BACKOFF ALGORITHM</div>
            <div class="fs-4 fw-bold text-sky-400">Exponential + Jitter</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MAX RETRY ATTEMPTS</div>
            <div class="fs-4 fw-bold text-emerald-400">5 Max Attempts</div>
        </div>
    </div>
</div>

<!-- DLQ Items Table & Enqueue Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-envelope-exclamation text-rose-400 me-2"></i>Dead-Letter Queue Inventory</span>
                <span class="badge bg-secondary" id="dlqBadge">2 ITEMS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Target Endpoint</th>
                                <th>Attempt</th>
                                <th>Backoff Delay</th>
                                <th>Last Error</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="dlqTableBody">
                            <tr><td colspan="5" class="text-center p-3 text-muted">Loading DLQ items...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-rose-400"><i class="bi bi-bug me-2"></i>Enqueue Test Failure</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">WEBHOOK TARGET URL</label>
                    <input type="text" id="targetUrlInput" class="form-control bg-black text-white border-secondary small" value="https://api.external-vendor.com/webhooks">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ERROR SIMULATION</label>
                    <select id="errorSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="HTTP_504_GATEWAY_TIMEOUT" selected>HTTP 504 Gateway Timeout</option>
                        <option value="HTTP_502_BAD_GATEWAY">HTTP 502 Bad Gateway</option>
                        <option value="CONNECTION_REFUSED">Connection Refused (ECONNREFUSED)</option>
                    </select>
                </div>

                <button class="btn btn-sm btn-outline-danger w-100 mb-3" onclick="enqueueFailedWebhook()">
                    <i class="bi bi-plus-circle me-1"></i> Simulate Failure to DLQ
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="dlqActionBox">
                    [Ready] Click 'Replay Pending DLQ' to test auto-retry pipeline...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadDlqItems() {
    try {
        const res = await apiFetch('/network/dlq/items');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricTotalDlq').innerText = `${d.total_items} ITEMS`;
            document.getElementById('metricPendingRetries').innerText = `${d.pending_count} RETRIES`;
            document.getElementById('dlqBadge').innerText = `${d.total_items} ITEMS`;

            renderDlqTable(d.items || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderDlqTable(items) {
    const tbody = document.getElementById('dlqTableBody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-3">DLQ is empty.</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(it => `
        <tr>
            <td class="fw-bold text-white font-monospace text-truncate" style="max-width: 180px;">${escapeHtml(it.target_url)}</td>
            <td><span class="badge bg-secondary">${it.attempt}/${it.max_attempts}</span></td>
            <td><span class="text-cyan-400 font-monospace">${it.backoff_delay_sec}s</span></td>
            <td><span class="text-danger small font-monospace">${escapeHtml(it.last_error)}</span></td>
            <td>
                <span class="badge ${it.status === 'SUCCESS' ? 'bg-success' : (it.status === 'PERMANENTLY_DEAD' ? 'bg-danger' : 'bg-warning text-dark')}">
                    ${escapeHtml(it.status)}
                </span>
            </td>
        </tr>
    `).join('');
}

async function enqueueFailedWebhook() {
    const url = document.getElementById('targetUrlInput').value.trim();
    const err = document.getElementById('errorSelect').value;

    try {
        const res = await apiFetch('/network/dlq/enqueue', {
            method: 'POST',
            body: JSON.stringify({
                target_url: url,
                payload: { test_event: 'order.failed', timestamp: Date.now() },
                error: err,
                attempt: 1
            })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') showToast('Failed webhook enqueued to DLQ', 'warning');
            loadDlqItems();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Enqueue error: ' + e.message, 'error');
    }
}

async function replayDlqDemo() {
    try {
        const res = await apiFetch('/network/dlq/replay', {
            method: 'POST',
            body: JSON.stringify({ force_all: true })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('dlqActionBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[REPLAYED] ${d.replayed_count} Webhooks Delivered Successfully</div>
                <div class="text-muted text-xs">Exhausted: ${d.exhausted_count} | Remaining Pending: ${d.total_pending}</div>
            `;

            if (typeof showToast === 'function') showToast(`Replayed ${d.replayed_count} DLQ webhooks`, 'success');
            loadDlqItems();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Replay error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDlqItems();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
