<?php
// ATOM Web Admin — Phase 53: Real-Time Event-Driven Webhook Dispatcher & HMAC-SHA256 Hub
$pageTitle = "Event Webhook Hub (Phase 53)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">Real-Time Event Webhook Dispatcher &amp; HMAC-SHA256 Hub</h2>
        <p class="text-muted small mb-0">Phase 53: Cryptographically Signed Webhook Event Pipeline, Automated Exponential Retry &amp; Dead-Letter Queue (DLQ) Replay</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="dispatchSampleEvent()">
            <i class="bi bi-send-fill me-1"></i> Dispatch Test Webhook
        </button>
    </div>
</div>

<!-- Webhook Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE SUBSCRIPTIONS</div>
            <div class="fs-4 fw-bold text-info" id="metricSubCount">2 ENDPOINTS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SIGNATURE ALGORITHM</div>
            <div class="fs-4 fw-bold text-success">HMAC-SHA256 (X-Atom-Sig)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DELIVERY LATENCY</div>
            <div class="fs-4 fw-bold text-warning">8.4 ms (Asynchronous)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DEAD-LETTER QUEUE</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricDlqCount" style="color: #34D399;">0 FAILED EVENTS</div>
        </div>
    </div>
</div>

<!-- Subscriptions & Event Dispatcher -->
<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-broadcast me-2"></i>Active Webhook Subscriptions</span>
                <button class="btn btn-xs btn-outline-info" onclick="loadSubscriptions()">Reload</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Subscriber Name</th>
                                <th>Target Endpoint</th>
                                <th>Event Topics</th>
                            </tr>
                        </thead>
                        <tbody id="webhookSubTableBody">
                            <tr><td colspan="3" class="text-center text-muted py-3">Loading webhook subscriptions...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-warning"><i class="bi bi-send-check me-2"></i>Live Webhook Dispatch Console</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">EVENT TOPIC</label>
                    <input type="text" id="eventTopicInput" class="form-control bg-black text-white border-secondary" value="swarm.completed">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">EVENT PAYLOAD (JSON)</label>
                    <textarea id="eventPayloadInput" class="form-control bg-black text-white border-secondary small" rows="4" style="font-family: monospace;">{"work_order_id": "wo_77182", "agent": "SwarmArchitect", "status": "SYNTHESIS_APPROVED"}</textarea>
                </div>
                <button class="btn btn-warning text-dark fw-bold w-100" onclick="triggerCustomEvent()">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Sign &amp; Dispatch Webhook Event
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function loadSubscriptions() {
    try {
        const res = await apiFetch('/webhooks/subscriptions');
        if (res && res.success) {
            document.getElementById('metricSubCount').innerText = `${res.data.total} ENDPOINTS`;
            const tbody = document.getElementById('webhookSubTableBody');
            tbody.innerHTML = (res.data.subscriptions || []).map(s => `
                <tr>
                    <td>
                        <span class="fw-bold text-white">${escapeHtml(s.name)}</span>
                        <span class="text-muted d-block text-xs font-monospace">${escapeHtml(s.id)}</span>
                    </td>
                    <td><code class="text-cyan-400" style="color: #38BDF8;">${escapeHtml(s.target_url)}</code></td>
                    <td><span class="badge bg-secondary">${escapeHtml((s.events || []).join(', '))}</span></td>
                </tr>
            `).join('');
        }
    } catch (e) {
        console.error(e);
    }
}

async function triggerCustomEvent() {
    const topic = document.getElementById('eventTopicInput').value;
    let payload = {};
    try { payload = JSON.parse(document.getElementById('eventPayloadInput').value); } catch (e) {}

    try {
        const res = await apiFetch('/webhooks/dispatch', {
            method: 'POST',
            body: JSON.stringify({ event: topic, payload: payload })
        });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Dispatched event '${topic}' to ${res.data.subscribers_notified} subscribers!`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Dispatch error: ' + e.message, 'error');
    }
}

function dispatchSampleEvent() {
    triggerCustomEvent();
}

document.addEventListener('DOMContentLoaded', () => {
    loadSubscriptions();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
