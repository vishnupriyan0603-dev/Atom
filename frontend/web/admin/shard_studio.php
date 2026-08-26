<?php
// ATOM Web Admin — Phase 87: Autonomous Multi-Tenant Database Shard Router & Consistent Hashing Ring
$pageTitle = "Database Shard Router (Phase 87)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #059669;">Database Shard Router &amp; Consistent Hash Ring</h2>
        <p class="text-muted small mb-0">Phase 87: Multi-Tenant Shard Topology, 64-Virtual-Node Consistent Hashing Ring &amp; Zero-Downtime Entity Rebalancing</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-success text-dark fw-bold" onclick="locateShardDemo()">
            <i class="bi bi-geo-alt-fill me-1"></i> Locate Shard for Tenant
        </button>
    </div>
</div>

<!-- Shard Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE PHYSICAL SHARDS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricShardsCount" style="color: #34D399;">3 SHARDS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL VIRTUAL NODES</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricVnodesCount">256 VNODES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HASH RING UNIFORMITY</div>
            <div class="fs-4 fw-bold text-info">99.4% Balance</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">REBALANCING IMPACT</div>
            <div class="fs-4 fw-bold text-warning">&le; 1/N Rehash</div>
        </div>
    </div>
</div>

<!-- Shards Table & Shard Routing Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-database-fill text-emerald-400 me-2"></i>Registered Database Shards</span>
                <span class="badge bg-secondary" id="shardsBadge">3 SHARDS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Shard ID</th>
                                <th>Host &amp; Port</th>
                                <th>Weight</th>
                                <th>V-Nodes on Ring</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="shardsTableBody">
                            <tr><td colspan="5" class="text-center p-3 text-muted">Loading shard topology...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-emerald-400"><i class="bi bi-compass-fill me-2"></i>Locate Shard for Key</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ROUTING KEY (TENANT / USER / DOC ID)</label>
                    <input type="text" id="routingKeyInput" class="form-control bg-black text-white border-secondary small" value="tenant_enterprise_42">
                </div>

                <button class="btn btn-sm btn-success text-dark fw-bold w-100 mb-3" onclick="locateShardDemo()">
                    <i class="bi bi-search me-1"></i> Resolve Hash Ring Target
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="shardResultBox">
                    [Ready] Enter a tenant ID and click 'Resolve Hash Ring Target'...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadShardStatus() {
    try {
        const res = await apiFetch('/database/shards/nodes');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricShardsCount').innerText = `${d.total_shards} SHARDS`;
            document.getElementById('metricVnodesCount').innerText = `${d.total_vnodes_on_ring} VNODES`;
            document.getElementById('shardsBadge').innerText = `${d.total_shards} SHARDS`;

            renderShardsTable(d.shards || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderShardsTable(shards) {
    const tbody = document.getElementById('shardsTableBody');
    if (!shards || shards.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-3">No shards registered.</td></tr>`;
        return;
    }

    tbody.innerHTML = shards.map(s => `
        <tr>
            <td class="fw-bold text-white"><i class="bi bi-hdd-stack-fill text-emerald-400 me-2"></i>${escapeHtml(s.shard_id)}</td>
            <td class="font-monospace text-xs text-muted">${escapeHtml(s.host)}:${s.port}</td>
            <td><span class="text-warning fw-bold">${s.weight}x</span></td>
            <td><span class="text-cyan-400 fw-bold">${s.virtual_nodes_count} vnodes</span></td>
            <td><span class="badge bg-success">${escapeHtml(s.status)}</span></td>
        </tr>
    `).join('');
}

async function locateShardDemo() {
    const key = document.getElementById('routingKeyInput').value.trim();

    try {
        const res = await apiFetch('/database/shards/locate', {
            method: 'POST',
            body: JSON.stringify({ routing_key: key })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('shardResultBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[RESOLVED] &rarr; ${escapeHtml(d.shard.shard_id)}</div>
                <div class="text-white font-monospace text-xs mb-1"><strong>DSN:</strong> ${escapeHtml(d.shard.dsn)}</div>
                <div class="text-muted text-xs"><strong>Key CRC32:</strong> ${d.key_hash} | <strong>V-Node Hash:</strong> ${d.matched_vnode_hash}</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Key '${key}' routed to ${d.shard.shard_id}`, 'success');
            }
            loadShardStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Locate error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadShardStatus();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
