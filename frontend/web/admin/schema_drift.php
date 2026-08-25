<?php
// ATOM Web Admin — Phase 65: Autonomous Database Schema Drift Detector & Auto-Sync Engine
$pageTitle = "Schema Drift Sync (Phase 65)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">Autonomous Database Schema Drift Detector &amp; Auto-Sync</h2>
        <p class="text-muted small mb-0">Phase 65: Live Table Column Drift Analysis, Missing Table Scanner &amp; 1-Click CodeIgniter 4 Migration Synthesizer</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-cyan text-white fw-bold" style="background: #06B6D4;" onclick="detectSchemaDrift()">
            <i class="bi bi-arrow-repeat me-1"></i> Scan Schema Drift
        </button>
    </div>
</div>

<!-- Drift Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DRIFT STATUS</div>
            <div class="fs-4 fw-bold text-warning" id="metricDriftStatus">DRIFT FOUND (3 TABLES)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MISSING TABLES</div>
            <div class="fs-4 fw-bold text-danger" id="metricMissingTables">1 TABLE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COLUMN DRIFTS</div>
            <div class="fs-4 fw-bold text-info" id="metricColumnDrifts">3 COLUMNS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MIGRATION SYNTHESIS</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">CodeIgniter 4 Forge</div>
        </div>
    </div>
</div>

<!-- Main Drift List & Migration Code Synthesizer -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-cyan-400"><i class="bi bi-exclamation-triangle-fill me-2"></i>Detected Schema Drifts</span>
                <span class="badge bg-warning text-dark" id="driftBadge">3 DRIFTS</span>
            </div>
            <div class="card-body p-3" id="driftListContainer">
                <div class="text-muted small">Scanning database schema...</div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-code-square me-2"></i>Synthesized CI4 Migration Code</span>
                <button class="btn btn-xs btn-outline-secondary" onclick="copyMigrationCode()"><i class="bi bi-clipboard me-1"></i>Copy Migration</button>
            </div>
            <div class="card-body">
                <textarea id="migrationCodeDisplay" class="form-control bg-black text-emerald-400 border-secondary small" rows="12" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>Click 'Scan Schema Drift' to synthesize migration...</textarea>
            </div>
        </div>
    </div>
</div>

<script>
let lastDetectedDrifts = [];

async function detectSchemaDrift() {
    try {
        const res = await apiFetch('/database/schema/detect-drift', { method: 'POST' });
        if (res && res.success) {
            const data = res.data;
            lastDetectedDrifts = data.drifts || [];

            document.getElementById('metricDriftStatus').innerText = data.status;
            document.getElementById('driftBadge').innerText = `${data.drift_count} DRIFTS`;

            renderDriftList(data.drifts || []);
            generateMigrationCode(data.drifts || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderDriftList(drifts) {
    const container = document.getElementById('driftListContainer');
    if (!drifts || drifts.length === 0) {
        container.innerHTML = `<div class="p-3 text-center text-success fw-bold"><i class="bi bi-check-circle me-1"></i> Schema is perfectly in sync!</div>`;
        return;
    }

    container.innerHTML = drifts.map(d => {
        const isMissing = d.type === 'MISSING_TABLE';
        return `
            <div class="p-2.5 rounded bg-black border ${isMissing ? 'border-danger/40' : 'border-warning/40'} mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-xs ${isMissing ? 'text-danger' : 'text-warning'}"><i class="bi ${isMissing ? 'bi-x-octagon-fill' : 'bi-plus-circle-fill'} me-1"></i>${escapeHtml(d.table)}</span>
                    <span class="badge ${isMissing ? 'bg-danger' : 'bg-warning text-dark'} text-xs">${d.type}</span>
                </div>
                <div class="text-xs text-muted">
                    ${isMissing ? 'Table missing from database' : `Missing cols: ${Object.keys(d.missing_columns || {}).join(', ')}`}
                </div>
            </div>
        `;
    }).join('');
}

async function generateMigrationCode(drifts) {
    try {
        const res = await apiFetch('/database/schema/generate-migration', {
            method: 'POST',
            body: JSON.stringify({ drifts: drifts, name: 'AutoSyncSchemaDrift' })
        });
        if (res && res.success) {
            document.getElementById('migrationCodeDisplay').value = res.data.code;
            if (typeof showToast === 'function') showToast('Schema drift analyzed & migration synthesized!', 'success');
        }
    } catch (e) {
        console.error(e);
    }
}

function copyMigrationCode() {
    navigator.clipboard.writeText(document.getElementById('migrationCodeDisplay').value);
    if (typeof showToast === 'function') showToast('Migration code copied to clipboard!', 'info');
}

document.addEventListener('DOMContentLoaded', () => {
    detectSchemaDrift();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
