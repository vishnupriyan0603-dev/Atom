<?php
// ATOM Web Admin — Phase 86: Real-Time Dynamic HTTP Reverse Proxy & Load Balancer Mesh
$pageTitle = "Reverse Proxy & Load Balancer (Phase 86)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #6366F1;">Reverse Proxy &amp; Multi-Algorithm Load Balancer</h2>
        <p class="text-muted small mb-0">Phase 86: Round-Robin, Weighted Distribution, IP-Hash Sticky Sessions, Dynamic Health Checks &amp; Header Rewriting</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-indigo text-white fw-bold" style="background: #6366F1;" onclick="routeProxyRequestDemo()">
            <i class="bi bi-send-fill me-1"></i> Dispatch Sample Request
        </button>
    </div>
</div>

<!-- Proxy Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE ALGORITHM</div>
            <div class="fs-4 fw-bold text-indigo-400" id="metricAlgo" style="color: #818CF8;">ROUND_ROBIN</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HEALTHY UPSTREAMS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricHealthyUpstreams" style="color: #34D399;">3 / 3 NODES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AVG PROXY LATENCY</div>
            <div class="fs-4 fw-bold text-info">~12 ms</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STICKY SESSIONS</div>
            <div class="fs-4 fw-bold text-pink-400">CRC32 IP-Hash</div>
        </div>
    </div>
</div>

<!-- Upstreams Table & Routing Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-server text-indigo-400 me-2"></i>Configured Upstream Backends</span>
                <span class="badge bg-secondary" id="nodesBadge">3 NODES</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Node ID</th>
                                <th>Target Endpoint</th>
                                <th>Weight</th>
                                <th>Latency</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="upstreamsTableBody">
                            <tr><td colspan="6" class="text-center p-3 text-muted">Loading proxy nodes...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-indigo-400"><i class="bi bi-sliders me-2"></i>Balancing Algorithm</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SELECT ROUTING STRATEGY</label>
                    <select id="algoSelect" class="form-select bg-black text-white border-secondary small" onchange="changeAlgorithm(this.value)">
                        <option value="round_robin" selected>Round-Robin (Sequential)</option>
                        <option value="weighted">Weighted Distribution (Capacity)</option>
                        <option value="ip_hash">IP-Hash (Sticky Client Sessions)</option>
                        <option value="least_latency">Least Latency (Fastest Response)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CLIENT IP ADDRESS</label>
                    <input type="text" id="clientIpInput" class="form-control bg-black text-white border-secondary small" value="203.0.113.45">
                </div>

                <button class="btn btn-sm btn-indigo text-white fw-bold w-100 mb-3" style="background: #6366F1;" onclick="routeProxyRequestDemo()">
                    <i class="bi bi-send-check-fill me-1"></i> Route Inbound Request
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="routeResultBox">
                    [Ready] Dispatch requests to test load balancing algorithm...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadUpstreamStatus() {
    try {
        const res = await apiFetch('/network/proxy/upstreams');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricAlgo').innerText = d.active_algorithm.toUpperCase();
            document.getElementById('metricHealthyUpstreams').innerText = `${d.healthy_nodes_count} / ${d.total_nodes} NODES`;
            document.getElementById('nodesBadge').innerText = `${d.total_nodes} NODES`;
            document.getElementById('algoSelect').value = d.active_algorithm;

            renderUpstreamsTable(d.upstreams || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderUpstreamsTable(nodes) {
    const tbody = document.getElementById('upstreamsTableBody');
    if (!nodes || nodes.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted p-3">No upstreams configured.</td></tr>`;
        return;
    }

    tbody.innerHTML = nodes.map(n => `
        <tr>
            <td class="fw-bold text-white"><i class="bi bi-hdd-rack me-2 text-indigo-400"></i>${escapeHtml(n.node_id)}</td>
            <td class="font-monospace text-xs text-muted">${escapeHtml(n.host)}:${n.port}</td>
            <td><span class="text-warning fw-bold">${n.weight}</span></td>
            <td><span class="text-info">${n.latency_ms} ms</span></td>
            <td><span class="badge ${n.healthy ? 'bg-success' : 'bg-danger'}">${n.healthy ? 'HEALTHY' : 'DOWN'}</span></td>
            <td>
                <button class="btn btn-xs ${n.healthy ? 'btn-outline-danger' : 'btn-outline-success'}" onclick="toggleNodeHealth('${escapeHtml(n.node_id)}', ${!n.healthy})">
                    ${n.healthy ? 'Mark Down' : 'Enable'}
                </button>
            </td>
        </tr>
    `).join('');
}

async function changeAlgorithm(algo) {
    try {
        const res = await apiFetch('/network/proxy/configure', {
            method: 'POST',
            body: JSON.stringify({ algorithm: algo })
        });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Algorithm switched to ${algo}`, 'success');
            loadUpstreamStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Config error: ' + e.message, 'error');
    }
}

async function toggleNodeHealth(nodeId, healthy) {
    try {
        const res = await apiFetch('/network/proxy/configure', {
            method: 'POST',
            body: JSON.stringify({ node_id: nodeId, healthy: healthy })
        });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Node ${nodeId} health updated`, 'info');
            loadUpstreamStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Toggle error: ' + e.message, 'error');
    }
}

async function routeProxyRequestDemo() {
    const ip = document.getElementById('clientIpInput').value.trim();

    try {
        const res = await apiFetch('/network/proxy/route', {
            method: 'POST',
            body: JSON.stringify({ client_ip: ip, path: '/api/v1/checkout' })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('routeResultBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[ROUTED] &rarr; ${escapeHtml(d.routed_node.node_id)}</div>
                <div class="text-white font-monospace text-xs mb-1"><strong>Target:</strong> ${escapeHtml(d.routed_node.target_url)}</div>
                <div class="text-muted text-xs"><strong>Algo:</strong> ${escapeHtml(d.algorithm_used)} | <strong>Latency:</strong> ${d.routed_node.latency_ms}ms</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Routed to ${d.routed_node.node_id} via ${d.algorithm_used}`, 'success');
            }
            loadUpstreamStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Route error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadUpstreamStatus();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
