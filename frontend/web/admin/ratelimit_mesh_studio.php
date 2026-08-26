<?php
// ATOM Web Admin — Phase 99: Autonomous Real-Time Global Distributed Rate Limiter & Token Mesh Coordinator
$pageTitle = "Distributed Rate Limiter & Token Mesh (Phase 99)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F43F5E;">Distributed Rate Limiter &amp; Token Mesh</h2>
        <p class="text-muted small mb-0">Phase 99: Multi-Node Token Mesh Sync, Sliding Window Fractional Refill &amp; Tiered Throttling Headers</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-danger fw-bold" onclick="consumeTokensDemo()">
            <i class="bi bi-lightning-charge-fill me-1"></i> Consume Tokens
        </button>
    </div>
</div>

<!-- Rate Limiter Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE MESH NODES</div>
            <div class="fs-4 fw-bold text-rose-400" id="metricNodes" style="color: #FB7185;">3 NODES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE CLIENT BUCKETS</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricBuckets" style="color: #FBBF24;">0 BUCKETS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">REFILL ALGORITHM</div>
            <div class="fs-4 fw-bold text-cyan-400">Sliding Token Bucket</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">THROTTLING PRECISION</div>
            <div class="fs-4 fw-bold text-emerald-400">Sub-ms Retry-After</div>
        </div>
    </div>
</div>

<!-- Token Consumption Simulator & Mesh Topology -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-rose-400"><i class="bi bi-speedometer2 me-2"></i>Token Consumption Simulator</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CLIENT KEY / API KEY</label>
                    <input type="text" id="clientKeyInput" class="form-control bg-black text-white border-secondary small" value="usr_live_demo_99">
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">TIER PLAN</label>
                        <select id="tierSelect" class="form-select bg-black text-white border-secondary small">
                            <option value="free">Free (10 Capacity, 2/s)</option>
                            <option value="developer" selected>Developer (100 Capacity, 20/s)</option>
                            <option value="enterprise">Enterprise (1000 Capacity, 200/s)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">TOKENS TO CONSUME</label>
                        <input type="number" id="tokensCostInput" class="form-control bg-black text-white border-secondary small" value="15" min="1">
                    </div>
                </div>

                <button class="btn btn-sm btn-danger fw-bold w-100 mb-3" onclick="consumeTokensDemo()">
                    <i class="bi bi-lightning-charge me-1"></i> Evaluate Rate Limit Token Consumption
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="consumeResultBox">
                    [Ready] Click 'Evaluate Rate Limit Token Consumption' to test token replenishment...
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-cyan-400"><i class="bi bi-globe me-2"></i>Global Mesh Nodes Topology</span>
                <span class="badge bg-secondary" id="meshBadge">3 NODES</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Node ID</th>
                                <th>Status</th>
                                <th>Last Ping</th>
                            </tr>
                        </thead>
                        <tbody id="meshTableBody">
                            <tr><td colspan="3" class="text-center p-3 text-muted">Loading mesh topology...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadMeshTopology() {
    try {
        const res = await apiFetch('/security/ratelimit/mesh');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricNodes').innerText = `${d.total_mesh_nodes} NODES`;
            document.getElementById('metricBuckets').innerText = `${d.total_active_buckets} BUCKETS`;
            document.getElementById('meshBadge').innerText = `${d.total_mesh_nodes} NODES`;

            const tbody = document.getElementById('meshTableBody');
            tbody.innerHTML = d.nodes.map(n => `
                <tr>
                    <td class="fw-bold text-white font-monospace"><i class="bi bi-hdd-network text-rose-400 me-2"></i>${escapeHtml(n.node_id)}</td>
                    <td><span class="badge bg-success">ONLINE</span></td>
                    <td><span class="text-muted small">Just now</span></td>
                </tr>
            `).join('');
        }
    } catch (e) {
        console.error(e);
    }
}

async function consumeTokensDemo() {
    const key = document.getElementById('clientKeyInput').value.trim();
    const cost = parseInt(document.getElementById('tokensCostInput').value) || 1;
    const tier = document.getElementById('tierSelect').value;

    try {
        const res = await apiFetch('/security/ratelimit/consume', {
            method: 'POST',
            body: JSON.stringify({
                client_key: key,
                tokens_cost: cost,
                tier: tier
            })
        });

        if (res && res.success) {
            const d = res.data;
            const statusColor = d.allowed ? 'text-emerald-400' : 'text-danger';

            document.getElementById('consumeResultBox').innerHTML = `
                <div class="${statusColor} fw-bold mb-1">[${d.allowed ? 'ALLOWED (200 OK)' : 'THROTTLED (429 TOO MANY REQUESTS)'}]</div>
                <div class="text-white text-xs mb-1"><strong>Remaining Tokens:</strong> ${d.remaining_tokens} / ${d.capacity}</div>
                <div class="text-muted text-xs mb-1 font-monospace">X-RateLimit-Limit: ${d.headers['X-RateLimit-Limit']}</div>
                <div class="text-muted text-xs font-monospace">X-RateLimit-Remaining: ${d.headers['X-RateLimit-Remaining']}</div>
                ${!d.allowed ? `<div class="text-danger text-xs mt-1 font-monospace">Retry-After: ${d.retry_after_sec} seconds</div>` : ''}
            `;

            if (typeof showToast === 'function') {
                showToast(`Tokens consumed: ${d.allowed ? 'ALLOWED' : 'THROTTLED'}`, d.allowed ? 'success' : 'warning');
            }
            loadMeshTopology();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Consume error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadMeshTopology();
    consumeTokensDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
