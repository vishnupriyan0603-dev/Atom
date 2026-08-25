<?php
// ATOM Web Admin — Phase 70 Landmark Milestone: Autonomous Redis Distributed Cache Invalidator & Thundering Herd Protector
$pageTitle = "Cache Invalidator (Phase 70)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EF4444;">Multi-Tenant Redis Cache Invalidator &amp; Stampede Shield</h2>
        <p class="text-muted small mb-0">Phase 70 Landmark: Tagged Multi-Tenant Invalidation Matrix, XFetch Probabilistic Early-Refresh &amp; Zero-Downtime Cache Mesh</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-danger text-white fw-bold" onclick="invalidateSelectedTag('users')">
            <i class="bi bi-trash-fill me-1"></i> Purge 'users' Tag
        </button>
    </div>
</div>

<!-- Cache Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CACHE HIT RATIO</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricHitRatio" style="color: #34D399;">100.0% HIT</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE CACHE KEYS</div>
            <div class="fs-4 fw-bold text-info" id="metricKeysCount">4 KEYS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">INDEXED TAGS</div>
            <div class="fs-4 fw-bold text-warning" id="metricTagsCount">4 TAGS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STAMPEDE SHIELD</div>
            <div class="fs-4 fw-bold text-danger" style="color: #EF4444;">XFetch Algorithm</div>
        </div>
    </div>
</div>

<!-- Tagged Keys Table & Invalidation Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-database-fill-gear me-2 text-danger"></i>Active Tagged Cache Entries</span>
                <span class="badge bg-secondary" id="keysBadge">4 KEYS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Key</th>
                                <th>Tenant</th>
                                <th>Tags</th>
                                <th>TTL</th>
                            </tr>
                        </thead>
                        <tbody id="cacheTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Loading cache keys...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-warning"><i class="bi bi-tag-fill me-2"></i>Purge By Tag</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TARGET TAG TO INVALIDATE</label>
                    <input type="text" id="targetTagInput" class="form-control bg-black text-white border-secondary small" value="users" placeholder="e.g. users or orders">
                </div>

                <div class="d-flex flex-wrap gap-1 mb-3" id="quickTagsContainer">
                    <button class="btn btn-xs btn-outline-secondary" onclick="setTag('users')">users</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="setTag('orders')">orders</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="setTag('tenant_alpha')">tenant_alpha</button>
                </div>

                <button class="btn btn-sm btn-danger text-white fw-bold w-100 mb-3" onclick="invalidateSelectedTag()">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Invalidate Matching Keys
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted">
                    <i class="bi bi-shield-check text-emerald-400 me-1"></i> XFetch prevents thundering herds by probabilistically computing early re-fetch windows under heavy concurrent load.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setTag(t) {
    document.getElementById('targetTagInput').value = t;
}

async function loadCacheStats() {
    try {
        const res = await apiFetch('/database/cache/stats');
        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricHitRatio').innerText = `${data.hit_ratio_pct}% HIT`;
            document.getElementById('metricKeysCount').innerText = `${data.total_keys} KEYS`;
            document.getElementById('metricTagsCount').innerText = `${data.active_tags_count} TAGS`;
            document.getElementById('keysBadge').innerText = `${data.total_keys} KEYS`;

            renderTable(data.keys || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderTable(keys) {
    const tbody = document.getElementById('cacheTableBody');
    if (!keys || keys.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">Cache store is empty.</td></tr>`;
        return;
    }

    tbody.innerHTML = keys.map(k => `
        <tr>
            <td class="fw-bold text-white"><i class="bi bi-key text-danger me-1"></i>${escapeHtml(k.key)}</td>
            <td><span class="badge bg-secondary">${escapeHtml(k.tenant_id)}</span></td>
            <td>${(k.tags || []).map(t => `<span class="badge bg-dark border border-secondary text-info me-1">${escapeHtml(t)}</span>`).join('')}</td>
            <td class="text-emerald-400">${k.ttl}s</td>
        </tr>
    `).join('');
}

async function invalidateSelectedTag(customTag) {
    const tag = customTag || document.getElementById('targetTagInput').value.trim();
    if (!tag) return;

    try {
        const res = await apiFetch('/database/cache/invalidate-tag', {
            method: 'POST',
            body: JSON.stringify({ tag: tag })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') {
                showToast(`Tag '${tag}' invalidated! Purged ${res.data.count} keys.`, 'success');
            }
            loadCacheStats();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Invalidation error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadCacheStats();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
