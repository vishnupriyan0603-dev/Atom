<?php
// ATOM Web Admin — Phase 72: Real-Time Dynamic SQL Query Explainer & Index Suggestion Synthesizer
$pageTitle = "SQL Query Explainer (Phase 72)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">SQL Query Explainer &amp; Index Synthesizer</h2>
        <p class="text-muted small mb-0">Phase 72: Real-Time Execution Plan Visualizer, Full-Table-Scan Detector &amp; 1-Click Composite Index DDL Generator</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="explainSqlQuery()">
            <i class="bi bi-lightning-charge-fill me-1"></i> Explain SQL Query
        </button>
    </div>
</div>

<!-- Explainer Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">QUERY EFFICIENCY SCORE</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricScore" style="color: #34D399;">85 / 100 (GOOD)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACCESS TYPE</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricAccess">range (Indexed)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TARGET TABLE</div>
            <div class="fs-4 fw-bold text-warning" id="metricTable">users</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EST. SCAN REDUCTION</div>
            <div class="fs-4 fw-bold text-pink-400" id="metricReduction" style="color: #EC4899;">98.5% FASTER</div>
        </div>
    </div>
</div>

<!-- SQL Input Sandbox & Suggested Indexes View -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-cyan-400"><i class="bi bi-terminal-fill me-2"></i>SQL Query Input</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ENTER SQL QUERY TO EXPLAIN</label>
                    <textarea id="sqlQueryInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="5">SELECT * FROM orders WHERE user_id = 42 AND created_at >= "2026-01-01" ORDER BY id DESC;</textarea>
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-xs btn-outline-secondary" onclick="setQuery('SELECT id, email FROM users WHERE tenant_id = 1 AND is_active = 1;')">Sample Query 1</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="setQuery('SELECT * FROM audit_logs WHERE created_at < NOW();')">Sample Query 2</button>
                </div>

                <button class="btn btn-sm btn-info text-dark fw-bold w-100" onclick="explainSqlQuery()">
                    <i class="bi bi-play-circle-fill me-1"></i> Analyze &amp; Generate Index Suggestions
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-check2-circle me-2"></i>Synthesized Composite Index DDL</span>
                <button class="btn btn-xs btn-outline-secondary" onclick="copyIndexDdl()"><i class="bi bi-clipboard me-1"></i>Copy DDL</button>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary mb-3">
                    <div class="text-xs text-muted mb-1 font-monospace">Recommended Index Command:</div>
                    <textarea id="indexDdlOutput" class="form-control bg-transparent text-emerald-400 border-0 p-0 font-monospace small" rows="3" readonly style="color: #34D399;">CREATE INDEX idx_orders_user_id_created_at ON orders (user_id, created_at);</textarea>
                </div>

                <div id="warningsContainer"></div>
            </div>
        </div>
    </div>
</div>

<script>
function setQuery(q) {
    document.getElementById('sqlQueryInput').value = q;
    explainSqlQuery();
}

async function explainSqlQuery() {
    const q = document.getElementById('sqlQueryInput').value.trim();

    try {
        const res = await apiFetch('/database/explainer/analyze', {
            method: 'POST',
            body: JSON.stringify({ query: q })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricScore').innerText = `${d.efficiency_score} / 100`;
            document.getElementById('metricAccess').innerText = `${d.access_type}`;
            document.getElementById('metricTable').innerText = d.table;

            const ddlBox = document.getElementById('indexDdlOutput');
            if (d.suggested_indexes && d.suggested_indexes.length > 0) {
                ddlBox.value = d.suggested_indexes[0].sql_ddl;
                document.getElementById('metricReduction').innerText = d.suggested_indexes[0].estimated_scan_reduction;
            } else {
                ddlBox.value = '-- Query is already optimal or no filter columns detected.';
            }

            const warnBox = document.getElementById('warningsContainer');
            if (d.warnings && d.warnings.length > 0) {
                warnBox.innerHTML = d.warnings.map(w => `<div class="p-2 bg-black border border-warning/40 rounded text-warning text-xs mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>${escapeHtml(w)}</div>`).join('');
            } else {
                warnBox.innerHTML = `<div class="p-2 bg-black border border-success/40 rounded text-emerald-400 text-xs"><i class="bi bi-check-circle-fill me-1"></i>No critical anti-patterns detected.</div>`;
            }

            if (typeof showToast === 'function') showToast(`Efficiency Score: ${d.efficiency_score}/100`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Explainer error: ' + e.message, 'error');
    }
}

function copyIndexDdl() {
    navigator.clipboard.writeText(document.getElementById('indexDdlOutput').value);
    if (typeof showToast === 'function') showToast('Index DDL copied to clipboard!', 'info');
}

document.addEventListener('DOMContentLoaded', () => {
    explainSqlQuery();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
