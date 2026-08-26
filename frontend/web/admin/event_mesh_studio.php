<?php
// ATOM Web Admin — Phase 92: Autonomous Multi-Channel Event Mesh & Pub/Sub Topic Broker Governor
$pageTitle = "Event Mesh & Topic Broker (Phase 92)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">Event Mesh &amp; Pub/Sub Topic Broker</h2>
        <p class="text-muted small mb-0">Phase 92: Hierarchical Wildcard Subscriptions (+ Single-Level, # Multi-Level), Fan-Out Governor &amp; Consumer Group Offset Tracking</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="publishEventDemo()">
            <i class="bi bi-broadcast me-1"></i> Publish Test Event
        </button>
    </div>
</div>

<!-- Event Mesh Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE TOPIC PATTERNS</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricPatterns" style="color: #22D3EE;">3 PATTERNS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">REGISTERED SUBSCRIBERS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricSubs" style="color: #34D399;">3 LISTENERS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">WILDCARD PROTOCOL</div>
            <div class="fs-4 fw-bold text-sky-400">MQTT/Kafka Standard</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DELIVERY LATENCY</div>
            <div class="fs-4 fw-bold text-info">&lt; 0.1 ms Fan-out</div>
        </div>
    </div>
</div>

<!-- Topic Channels & Publisher Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-diagram-3 text-cyan-400 me-2"></i>Active Topic Channels &amp; Metrics</span>
                <span class="badge bg-secondary" id="topicsBadge">3 TOPICS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Topic Channel</th>
                                <th>Published</th>
                                <th>Delivered</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="topicsTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Loading topics...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-cyan-400"><i class="bi bi-send-check me-2"></i>Event Dispatcher Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TARGET TOPIC</label>
                    <input type="text" id="topicInput" class="form-control bg-black text-white border-secondary small font-monospace" value="telemetry/sensor_alpha/temperature">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PAYLOAD (JSON)</label>
                    <textarea id="payloadJsonInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="4">{
  "sensor_id": "sensor_alpha",
  "temperature_c": 24.8,
  "humidity_pct": 52.0
}</textarea>
                </div>

                <button class="btn btn-sm btn-info text-dark fw-bold w-100 mb-3" onclick="publishEventDemo()">
                    <i class="bi bi-broadcast me-1"></i> Fan-Out to Subscribed Nodes
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="publishResultBox">
                    [Ready] Publish to test wildcard pattern matching (+ and #)...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadMeshStatus() {
    try {
        const res = await apiFetch('/network/mesh/topics');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricPatterns').innerText = `${d.total_topic_patterns} PATTERNS`;
            document.getElementById('metricSubs').innerText = `${d.total_topic_patterns} LISTENERS`;
            document.getElementById('topicsBadge').innerText = `${d.total_active_topics} TOPICS`;

            renderTopicsTable(d.topics || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderTopicsTable(topics) {
    const tbody = document.getElementById('topicsTableBody');
    if (!topics || topics.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">No active topic channels yet.</td></tr>`;
        return;
    }

    tbody.innerHTML = topics.map(t => `
        <tr>
            <td class="fw-bold text-white font-monospace"><i class="bi bi-hash text-cyan-400 me-1"></i>${escapeHtml(t.topic)}</td>
            <td><span class="text-cyan-400 fw-bold">${t.published_count} msgs</span></td>
            <td><span class="text-emerald-400 fw-bold">${t.delivered_count} fanouts</span></td>
            <td><span class="badge bg-success">ACTIVE</span></td>
        </tr>
    `).join('');
}

async function publishEventDemo() {
    const topic = document.getElementById('topicInput').value.trim();
    let payload = {};
    try {
        payload = JSON.parse(document.getElementById('payloadJsonInput').value);
    } catch (e) {
        if (typeof showToast === 'function') showToast('Invalid JSON payload', 'error');
        return;
    }

    try {
        const res = await apiFetch('/network/mesh/publish', {
            method: 'POST',
            body: JSON.stringify({
                topic: topic,
                payload: payload,
                publisher_id: 'web_admin_studio'
            })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('publishResultBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[PUBLISHED] Matched ${d.matched_subscribers_count} Subscribers</div>
                <div class="text-white text-xs mb-1"><strong>Topic:</strong> ${escapeHtml(d.topic)}</div>
                <div class="text-muted text-xs"><strong>Matched Patterns:</strong> ${d.subscribers.map(s => s.pattern_matched).join(', ') || 'None'}</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Delivered to ${d.matched_subscribers_count} subscribers`, 'success');
            }
            loadMeshStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Publish error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadMeshStatus();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
