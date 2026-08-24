<?php
// ATOM Web Admin — Phase 32: Enterprise Sandboxed Plugin Marketplace Dashboard
$pageTitle = "Plugin Marketplace & Sandbox";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #8B5CF6;">Enterprise Plugin Marketplace &amp; Sandbox</h2>
        <p class="text-muted small mb-0">Cryptographically signed packages, capability permission boundaries &amp; hot-reloadable sandboxed execution</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%); border: none;" onclick="loadPlugins('all')">
            <i class="bi bi-shop me-1"></i> Sync Marketplace
        </button>
    </div>
</div>

<!-- Marketplace Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MARKETPLACE STATUS</div>
            <div class="fs-4 fw-bold" style="color:#8B5CF6;" id="metricMarketStatus">ONLINE (Verified)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PACKAGE SIGNING</div>
            <div class="fs-4 fw-bold text-success" id="metricSigning">HMAC-SHA256</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">INSTALLED PLUGINS</div>
            <div class="fs-4 fw-bold text-info" id="metricInstalledCount">0 ACTIVE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SANDBOX ISOLATION</div>
            <div class="fs-4 fw-bold text-warning" id="metricSandbox">STRICT (64MB Cap)</div>
        </div>
    </div>
</div>

<!-- Category Filters -->
<div class="d-flex gap-2 mb-4">
    <button class="btn btn-sm btn-primary category-btn active" onclick="filterCategory('all', this)">All Categories</button>
    <button class="btn btn-sm btn-outline-secondary category-btn" onclick="filterCategory('database', this)">Database</button>
    <button class="btn btn-sm btn-outline-secondary category-btn" onclick="filterCategory('security', this)">Security</button>
    <button class="btn btn-sm btn-outline-secondary category-btn" onclick="filterCategory('cloud', this)">Cloud</button>
    <button class="btn btn-sm btn-outline-secondary category-btn" onclick="filterCategory('math', this)">Math</button>
    <button class="btn btn-sm btn-outline-secondary category-btn" onclick="filterCategory('devops', this)">DevOps</button>
</div>

<!-- Marketplace Catalog Grid -->
<div class="row g-4 mb-4" id="pluginGrid">
    <div class="col-12 text-center text-muted py-5">
        <div class="spinner-border text-primary mb-2" role="status"></div>
        <div>Loading verified enterprise plugins...</div>
    </div>
</div>

<!-- Interactive Sandbox Execution Runner -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold" style="color:#8B5CF6;"><i class="bi bi-shield-lock me-2"></i>Sandboxed Plugin Capability Runner</span>
        <span class="badge bg-secondary" id="execStatusBadge">IDLE</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label text-muted small fw-bold">CAPABILITY METHOD</label>
                <select id="methodSelect" class="form-select bg-black text-white border-secondary mb-3">
                    <option value="explain_query">explain_query (Database Query Optimizer)</option>
                    <option value="suggest_indexes">suggest_indexes (Database Query Optimizer)</option>
                    <option value="scan_dependencies">scan_dependencies (CVE Security Scanner)</option>
                    <option value="upload_vault">upload_vault (AWS S3 Exporter)</option>
                    <option value="inspect_containers">inspect_containers (Docker Monitor)</option>
                </select>

                <label class="form-label text-muted small fw-bold">INPUT PARAMETERS (JSON)</label>
                <textarea id="execParams" class="form-control bg-black text-white border-secondary mb-3" rows="3" style="font-family: monospace; font-size: 12px;">{"query": "SELECT * FROM chats WHERE user_id = 1"}</textarea>

                <button class="btn btn-sm w-100 text-white fw-bold" style="background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);" onclick="executeInSandbox()">
                    <i class="bi bi-play-circle me-1"></i> Execute in Isolated Sandbox
                </button>
            </div>
            <div class="col-md-7">
                <label class="form-label text-muted small fw-bold">SANDBOX TELEMETRY &amp; RESULT</label>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 200px;">
                    <div id="execOutput" class="small text-emerald-400" style="font-family: monospace; white-space: pre-wrap; color:#34D399;">
Select a method and click execute to run untrusted plugin capabilities inside the permission-controlled sandbox.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let allPlugins = [];

async function loadPlugins(category = 'all') {
    try {
        const url = (category && category !== 'all') ? `/api/v1/marketplace/plugins?category=${category}` : '/api/v1/marketplace/plugins';
        const res = await fetch(url);
        const data = await res.json();
        if (data.success) {
            allPlugins = data.data.catalog;
            renderPlugins(allPlugins);
            const installedCount = allPlugins.filter(p => p.is_installed).length;
            document.getElementById('metricInstalledCount').innerText = `${installedCount} ACTIVE`;
        }
    } catch (e) {
        document.getElementById('pluginGrid').innerHTML = `<div class="col-12 text-danger">Failed to load marketplace: ${e.message}</div>`;
    }
}

function renderPlugins(plugins) {
    const grid = document.getElementById('pluginGrid');
    if (!plugins.length) {
        grid.innerHTML = '<div class="col-12 text-muted text-center py-4">No plugins found in this category.</div>';
        return;
    }

    grid.innerHTML = plugins.map(p => `
        <div class="col-md-4">
            <div class="card bg-dark border-secondary text-white h-100 p-3 d-flex flex-col justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-secondary uppercase text-[10px]">${p.category}</span>
                        <span class="badge ${p.is_installed ? (p.is_enabled ? 'bg-success' : 'bg-warning text-dark') : 'bg-dark border border-secondary text-muted'}">
                            ${p.is_installed ? (p.is_enabled ? 'INSTALLED & ACTIVE' : 'DISABLED') : 'AVAILABLE'}
                        </span>
                    </div>
                    <h5 class="fw-bold mb-1" style="color: #A78BFA;">${p.name}</h5>
                    <div class="text-muted small mb-2">by ${p.author} • v${p.version} • ★ ${p.rating}</div>
                    <p class="small text-gray-300 mb-3">${p.description}</p>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold mb-1">PERMISSIONS:</div>
                        <div class="d-flex gap-1 flex-wrap">
                            ${p.permissions.length ? p.permissions.map(perm => `<span class="badge bg-black border border-secondary text-warning">${perm}</span>`).join('') : '<span class="badge bg-black border border-secondary text-success">None (Pure Sandbox)</span>'}
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 pt-2 border-top border-secondary">
                    ${!p.is_installed 
                        ? `<button class="btn btn-sm btn-primary w-100" onclick="installPlugin('${p.id}')"><i class="bi bi-download me-1"></i> Install</button>`
                        : `<button class="btn btn-sm ${p.is_enabled ? 'btn-outline-warning' : 'btn-outline-success'} w-50" onclick="togglePlugin('${p.id}', ${!p.is_enabled})">
                            ${p.is_enabled ? 'Disable' : 'Enable'}
                           </button>
                           <button class="btn btn-sm btn-outline-danger w-50" onclick="uninstallPlugin('${p.id}')">
                            Uninstall
                           </button>`
                    }
                </div>
            </div>
        </div>
    `).join('');
}

async function installPlugin(pluginId) {
    try {
        const res = await fetch('/api/v1/marketplace/install', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: pluginId})
        });
        const data = await res.json();
        if (data.success) {
            loadPlugins();
        } else {
            alert('Installation failed: ' + data.message);
        }
    } catch (e) {
        alert('Network error: ' + e.message);
    }
}

async function uninstallPlugin(pluginId) {
    try {
        const res = await fetch('/api/v1/marketplace/uninstall', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: pluginId})
        });
        const data = await res.json();
        if (data.success) {
            loadPlugins();
        }
    } catch (e) {
        alert('Uninstall failed: ' + e.message);
    }
}

async function togglePlugin(pluginId, enable) {
    try {
        const res = await fetch('/api/v1/marketplace/toggle', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: pluginId, enabled: enable})
        });
        const data = await res.json();
        if (data.success) {
            loadPlugins();
        }
    } catch (e) {
        alert('Toggle failed: ' + e.message);
    }
}

async function executeInSandbox() {
    const method = document.getElementById('methodSelect').value;
    let params = {};
    try {
        params = JSON.parse(document.getElementById('execParams').value);
    } catch (e) {
        params = {};
    }

    document.getElementById('execStatusBadge').innerText = 'EXECUTING...';
    try {
        const res = await fetch('/api/v1/marketplace/execute', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({method: method, params: params})
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('execStatusBadge').innerText = 'COMPLETED';
            document.getElementById('execOutput').innerText = JSON.stringify(data.data, null, 2);
        } else {
            document.getElementById('execStatusBadge').innerText = 'FAILED';
            document.getElementById('execOutput').innerText = 'Sandbox Error: ' + data.message;
        }
    } catch (e) {
        document.getElementById('execOutput').innerText = 'Execution error: ' + e.message;
    }
}

function filterCategory(cat, btn) {
    document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
    document.querySelectorAll('.category-btn').forEach(b => b.classList.add('btn-outline-secondary'));
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-primary', 'active');
    loadPlugins(cat);
}

// Initial load
document.addEventListener('DOMContentLoaded', () => loadPlugins('all'));
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
