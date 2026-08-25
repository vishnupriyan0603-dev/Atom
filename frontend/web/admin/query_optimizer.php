<?php
// ATOM Web Admin — Phase 52: Autonomous SQL Query Index Optimizer & Migration Synthesizer
$pageTitle = "SQL Index Optimizer (Phase 52)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">Autonomous SQL Query Index Optimizer &amp; Migration Synthesizer</h2>
        <p class="text-muted small mb-0">Phase 52: Full Table Scan Elimination, ESR (Equality, Sort, Range) Composite B-Tree Index Heuristics &amp; CodeIgniter 4 Migration Generator</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="runOptimizerDemo()">
            <i class="bi bi-magic me-1"></i> Run Index Demo
        </button>
    </div>
</div>

<!-- Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ESTIMATED COST REDUCTION</div>
            <div class="fs-4 fw-bold text-success" id="metricCostReduction">94.8% REDUCTION</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">INDEXING STRATEGY</div>
            <div class="fs-4 fw-bold text-info">ESR Composite B-Tree</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACCESS PATTERN</div>
            <div class="fs-4 fw-bold text-warning" id="metricAccessPattern">Index Seek $O(\log N)$</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DIALECT COMPATIBILITY</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">MySQL • SQLite • Postgres</div>
        </div>
    </div>
</div>

<!-- Main Section: Query Input & Recommendation -->
<div class="row g-4 mb-4">
    
    <!-- 1. SQL Query Input -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-database me-2"></i>Slow SQL Query Input</span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-xs btn-outline-secondary" onclick="loadSampleOrdersQuery()">Orders Query</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="loadSampleAuditQuery()">Audit Query</button>
                </div>
            </div>
            <div class="card-body">
                <textarea id="sqlQueryInput" class="form-control bg-black text-white border-secondary small mb-3" rows="10" style="font-family: monospace; font-size: 12px;">SELECT id, user_id, amount, status, created_at
FROM orders
WHERE user_id = 42 AND status = 'COMPLETED' AND created_at >= '2026-01-01'
ORDER BY created_at DESC
LIMIT 50;</textarea>

                <button class="btn btn-info text-dark fw-bold w-100" onclick="analyzeSqlQuery()">
                    <i class="bi bi-cpu-fill me-1"></i> Analyze &amp; Compute Composite Index
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Index Recommendation & Cost -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-patch-check-fill me-2"></i>Recommended Composite B-Tree Index</span>
                <span class="badge bg-success" id="indexStatusBadge">OPTIMAL</span>
            </div>
            <div class="card-body">
                
                <div class="p-3 bg-black border border-secondary rounded mb-3">
                    <div class="text-muted text-xs fw-bold">RECOMMENDED INDEX NAME</div>
                    <div class="fs-5 fw-bold text-info font-monospace" id="recommendedIndexName">idx_orders_user_id_status_created_at</div>
                    <div class="text-muted text-xs mt-1">Columns: <span class="text-white fw-bold font-monospace" id="recommendedIndexCols">(user_id, status, created_at)</span></div>
                </div>

                <div class="row g-2 text-center text-xs mb-3">
                    <div class="col-6 p-2 bg-black border border-danger rounded">
                        <span class="text-muted d-block">Cost Before (Scan)</span>
                        <span class="fw-bold text-danger fs-6" id="costBeforeVal">1000.0</span>
                    </div>
                    <div class="col-6 p-2 bg-black border border-success rounded">
                        <span class="text-muted d-block">Cost After (Index Seek)</span>
                        <span class="fw-bold text-success fs-6" id="costAfterVal">41.7 (-95.8%)</span>
                    </div>
                </div>

                <div class="text-muted text-xs">
                    <i class="bi bi-info-circle me-1 text-info"></i> Applied <strong>ESR Heuristic</strong>: Equality predicates (<code>user_id</code>, <code>status</code>) positioned first, followed by sort/range column (<code>created_at</code>) to prevent file-sort overhead.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Synthesized Migrations (SQL DDL & CodeIgniter 4) -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6"><i class="bi bi-file-earmark-code-fill me-2 text-warning"></i>Synthesized Database Migration Scripts</span>
        <button class="btn btn-xs btn-outline-secondary" onclick="copyMigrationCode()"><i class="bi bi-clipboard me-1"></i>Copy Migration</button>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">PURE SQL DDL MIGRATION</label>
                <textarea id="sqlDdlOutput" class="form-control bg-black text-warning border-secondary small" rows="7" style="font-family: monospace; font-size: 12px;" readonly>CREATE INDEX idx_orders_user_id_status_created_at ON orders (user_id, status, created_at);</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">CODEIGNITER 4 PHP MIGRATION CLASS</label>
                <textarea id="ci4MigrationOutput" class="form-control bg-black text-emerald-400 border-secondary small" rows="7" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>// CI4 Migration class will appear here...</textarea>
            </div>
        </div>
    </div>
</div>

<script>
async function analyzeSqlQuery() {
    const sql = document.getElementById('sqlQueryInput').value;
    try {
        const res = await apiFetch('/database/query-optimizer/analyze', {
            method: 'POST',
            body: JSON.stringify({ sql: sql })
        });

        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricCostReduction').innerText = `${data.cost_reduction_pct}% REDUCTION`;
            document.getElementById('recommendedIndexName').innerText = data.recommended_index.name;
            document.getElementById('recommendedIndexCols').innerText = `(${data.recommended_index.columns.join(', ')})`;
            document.getElementById('costBeforeVal').innerText = data.estimated_cost_before;
            document.getElementById('costAfterVal').innerText = `${data.estimated_cost_after} (-${data.cost_reduction_pct}%)`;

            document.getElementById('sqlDdlOutput').value = data.sql_ddl_migration;
            document.getElementById('ci4MigrationOutput').value = data.ci4_php_migration;

            if (typeof showToast === 'function') showToast(`Computed index: ${data.recommended_index.name} (${data.cost_reduction_pct}% faster)`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Index optimization error: ' + e.message, 'error');
    }
}

function loadSampleOrdersQuery() {
    document.getElementById('sqlQueryInput').value = `SELECT id, user_id, amount, status, created_at
FROM orders
WHERE user_id = 42 AND status = 'COMPLETED' AND created_at >= '2026-01-01'
ORDER BY created_at DESC
LIMIT 50;`;
    analyzeSqlQuery();
}

function loadSampleAuditQuery() {
    document.getElementById('sqlQueryInput').value = `SELECT *
FROM audit_logs
WHERE tenant_id = 'tenant_enterprise_01' AND severity = 'CRITICAL' AND timestamp >= 1787600000
ORDER BY timestamp DESC;`;
    analyzeSqlQuery();
}

function copyMigrationCode() {
    navigator.clipboard.writeText(document.getElementById('ci4MigrationOutput').value);
    if (typeof showToast === 'function') showToast('CodeIgniter 4 migration copied!', 'info');
}

function runOptimizerDemo() {
    loadSampleOrdersQuery();
}

document.addEventListener('DOMContentLoaded', () => {
    runOptimizerDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
