<?php
// ATOM Web Admin — Phase 28: Real-Time WebSocket & Cross-Device State Sync Dashboard
$pageTitle = "Real-Time Sync & WebSocket Stream";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">Real-Time Sync &amp; WebSocket Stream</h2>
        <p class="text-muted small mb-0">Cross-device state replication (CRDT/Vector Clock) &amp; bidirectional event broadcasting</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm" style="background: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%); border: none; color: white;" onclick="broadcastSampleNotification()">
            <i class="bi bi-broadcast me-1"></i> Broadcast Ping
        </button>
    </div>
</div>

<!-- Sync Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SYNC REPLICATION</div>
            <div class="fs-4 fw-bold text-success" id="metricSyncStatus">ONLINE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">VECTOR CLOCK</div>
            <div class="fs-4 fw-bold text-info" id="metricVectorClock">#101</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CONNECTED PEERS</div>
            <div class="fs-4 fw-bold text-warning" id="metricPeerCount">3 PEERS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EVENT STREAM</div>
            <div class="fs-4 fw-bold" style="color:#06B6D4;" id="metricEventStream">ACTIVE SSE</div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Panel 1: Connected Peer Topology Matrix -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#06B6D4;"><i class="bi bi-diagram-3 me-2"></i>Connected Peer Topology Matrix</span>
                <span class="badge bg-primary">TOPOLOGY</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0" style="font-size: 13px;">
                        <thead>
                            <tr class="text-muted border-secondary">
                                <th>Device Name</th>
                                <th>Type</th>
                                <th>IP Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="peerTableBody">
                            <tr>
                                <td><strong>ATOM Desktop (WPF)</strong></td>
                                <td><span class="badge bg-info text-dark">desktop_wpf</span></td>
                                <td><code>127.0.0.1</code></td>
                                <td><span class="badge bg-success">ONLINE</span></td>
                            </tr>
                            <tr>
                                <td><strong>ATOM Mobile (Flutter)</strong></td>
                                <td><span class="badge bg-warning text-dark">mobile_flutter</span></td>
                                <td><code>192.168.1.105</code></td>
                                <td><span class="badge bg-success">ONLINE</span></td>
                            </tr>
                            <tr>
                                <td><strong>ATOM Web Admin</strong></td>
                                <td><span class="badge bg-secondary">web_admin</span></td>
                                <td><code>127.0.0.1</code></td>
                                <td><span class="badge bg-success">ONLINE</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 2: Real-Time Event Stream Monitor -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#38BDF8;"><i class="bi bi-activity me-2"></i>Real-Time Event Stream Monitor</span>
                <span class="badge bg-info text-dark">LIVE LOG</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black border border-secondary rounded" id="eventStreamLog" style="min-height: 220px; max-height: 250px; overflow-y: auto; font-family: monospace; font-size: 12px; color: #E2E8F0;">
                    <div class="text-muted small">[STREAM STARTED] Subscribed to real-time sync event channel.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 3: Interactive Event Broadcaster -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#F59E0B;"><i class="bi bi-broadcast-pin me-2"></i>Interactive Event Broadcaster</span>
                <span class="badge bg-warning text-dark">BROADCAST</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Event Channel</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" id="broadcastEventName" value="system:notification">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Payload (JSON)</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="broadcastPayloadArea" rows="3" style="font-family: monospace; font-size: 12px;">{"message": "Hello from Web Admin!", "urgency": "normal"}</textarea>
                </div>
                <button class="btn btn-warning btn-sm w-100" onclick="sendCustomBroadcast()">
                    <i class="bi bi-send me-1"></i> Broadcast to All Connected Peers
                </button>
            </div>
        </div>
    </div>

    <!-- Panel 4: State Delta Pusher (CRDT Simulator) -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#10B981;"><i class="bi bi-arrow-repeat me-2"></i>State Delta Pusher (CRDT Simulator)</span>
                <span class="badge bg-success">STATE REPLICATION</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">Entity Type</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" id="deltaEntityType" value="memory">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">Entity ID</label>
                        <input type="text" class="form-control bg-dark text-white border-secondary" id="deltaEntityId" value="mem_gate_2028">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">State Payload (JSON)</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="deltaPayloadArea" rows="2" style="font-family: monospace; font-size: 12px;">{"topic": "GATE 2028 Prep", "priority": "high"}</textarea>
                </div>
                <button class="btn btn-success btn-sm w-100" onclick="pushStateDeltaLive()">
                    <i class="bi bi-cloud-upload me-1"></i> Record &amp; Replicate Delta
                </button>
            </div>
        </div>
    </div>

</div>

<script>
const API_BASE = window.ATOM_API_BASE || '/api';
const TOKEN    = localStorage.getItem('atom_token') || '';

function apiFetch(path, opts = {}) {
    return fetch(API_BASE + path, {
        ...opts,
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + TOKEN, ...(opts.headers || {}) }
    }).then(r => r.json());
}

function loadSyncTopology() {
    apiFetch('/sync/peers').then(res => {
        if (!res.success) return;
        const d = res.data;
        document.getElementById('metricVectorClock').textContent = '#' + (d.current_vector_clock || 101);
        document.getElementById('metricPeerCount').textContent = `${d.active_peers_count || 3} PEERS`;

        const peers = d.peers || [];
        const tbody = document.getElementById('peerTableBody');
        if (peers.length > 0) {
            tbody.innerHTML = peers.map(p => `
                <tr>
                    <td><strong>${p.device_name}</strong></td>
                    <td><span class="badge bg-info text-dark">${p.client_type}</span></td>
                    <td><code>${p.ip_address}</code></td>
                    <td><span class="badge bg-success">${p.status.toUpperCase()}</span></td>
                </tr>
            `).join('');
        }
    }).catch(() => {});
}

function logEvent(text, type = 'info') {
    const box = document.getElementById('eventStreamLog');
    const time = new Date().toLocaleTimeString();
    const color = type === 'delta' ? '#34D399' : (type === 'broadcast' ? '#FBBF24' : '#38BDF8');
    box.innerHTML = `<div style="color:${color};">[${time}] ${text}</div>` + box.innerHTML;
}

function sendCustomBroadcast() {
    const event = document.getElementById('broadcastEventName').value.trim() || 'system:notification';
    let payload = {};
    try {
        payload = JSON.parse(document.getElementById('broadcastPayloadArea').value);
    } catch (_) {
        payload = { raw: document.getElementById('broadcastPayloadArea').value };
    }

    apiFetch('/sync/broadcast', {
        method: 'POST',
        body: JSON.stringify({ event: event, payload: payload })
    }).then(res => {
        if (res.success) {
            logEvent(`BROADCAST: ${event} -> ${JSON.stringify(payload)}`, 'broadcast');
            loadSyncTopology();
        }
    });
}

function broadcastSampleNotification() {
    apiFetch('/sync/broadcast', {
        method: 'POST',
        body: JSON.stringify({ event: 'system:heartbeat', payload: { ping: 'pong', time: Date.now() } })
    }).then(res => {
        if (res.success) {
            logEvent('BROADCAST: system:heartbeat dispatched to all peers', 'broadcast');
        }
    });
}

function pushStateDeltaLive() {
    const type = document.getElementById('deltaEntityType').value.trim() || 'memory';
    const id = document.getElementById('deltaEntityId').value.trim() || 'entity_01';
    let payload = {};
    try {
        payload = JSON.parse(document.getElementById('deltaPayloadArea').value);
    } catch (_) {
        payload = { text: document.getElementById('deltaPayloadArea').value };
    }

    apiFetch('/sync/push', {
        method: 'POST',
        body: JSON.stringify({ entity_type: type, entity_id: id, payload: payload, device_id: 'web_admin' })
    }).then(res => {
        if (res.success) {
            logEvent(`DELTA PUSHED: [${type} / ${id}] Vector Clock incremented.`, 'delta');
            loadSyncTopology();
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    loadSyncTopology();
    setInterval(loadSyncTopology, 15000);
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
