<?php
// ATOM Web Admin — Phase 98: Autonomous Dynamic Schema Migration Engine & Zero-Downtime DDL Planner
$pageTitle = "Schema Migration & Zero-Downtime DDL (Phase 98)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #6366F1;">Zero-Downtime Schema Migrations &amp; DDL</h2>
        <p class="text-muted small mb-0">Phase 98: Online Non-Blocking DDL (ALGORITHM=INPLACE, LOCK=NONE), Shadow Table Swaps &amp; Rollback Ledgers</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-primary fw-bold" onclick="planMigrationDemo()">
            <i class="bi bi-play-circle-fill me-1"></i> Generate DDL Plan
        </button>
    </div>
</div>

<!-- Migration Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">APPLIED MIGRATIONS</div>
            <div class="fs-4 fw-bold text-indigo-400" id="metricMigrations" style="color: #818CF8;">1 APPLIED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ONLINE DDL STRATEGY</div>
            <div class="fs-4 fw-bold text-cyan-400">INPLACE, LOCK=NONE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TABLE LOCK RISK</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricRisk">Zero-Lock Safe</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ROLLBACK SAFETY</div>
            <div class="fs-4 fw-bold text-pink-400">Atomic Reverse DDL</div>
        </div>
    </div>
</div>

<!-- DDL Planner & History Matrix -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-indigo-400"><i class="bi bi-diagram-3 me-2"></i>Online DDL Migration Planner</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">TARGET TABLE</label>
                        <input type="text" id="tableNameInput" class="form-control bg-black text-white border-secondary small" value="customers">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">OPERATION</label>
                        <select id="operationSelect" class="form-select bg-black text-white border-secondary small">
                            <option value="add_column" selected>Add Column (Instant)</option>
                            <option value="add_index">Add Concurrent Index</option>
                            <option value="modify_column">Modify Column (Shadow Table)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">COLUMN / INDEX NAME</label>
                    <input type="text" id="fieldNameInput" class="form-control bg-black text-white border-secondary small" value="billing_tier">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DATA TYPE / SPECIFICATION</label>
                    <input type="text" id="dataTypeInput" class="form-control bg-black text-white border-secondary small" value="VARCHAR(50)">
                </div>

                <button class="btn btn-sm btn-primary fw-bold w-100 mb-3" onclick="planMigrationDemo()">
                    <i class="bi bi-file-earmark-code me-1"></i> Generate Zero-Downtime DDL Plan
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="planOutputBox">
                    [Ready] Click 'Generate Zero-Downtime DDL Plan' to preview non-blocking SQL...
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-cyan-400"><i class="bi bi-clock-history me-2"></i>Migration Audit Ledger</span>
                <span class="badge bg-secondary" id="historyBadge">1 VERSIONS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Version</th>
                                <th>Table</th>
                                <th>Strategy</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Loading migration history...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPlan = null;

async function loadHistory() {
    try {
        const res = await apiFetch('/database/migration/history');
        if (res && res.success) {
            const list = res.data || [];
            document.getElementById('metricMigrations').innerText = `${list.length} APPLIED`;
            document.getElementById('historyBadge').innerText = `${list.length} VERSIONS`;

            const tbody = document.getElementById('historyTableBody');
            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">No migrations applied yet.</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(m => `
                <tr>
                    <td class="fw-bold text-indigo-400 font-monospace text-xs">${escapeHtml(m.version)}</td>
                    <td class="text-white">${escapeHtml(m.table)}</td>
                    <td><span class="badge bg-secondary text-xs">${escapeHtml(m.strategy)}</span></td>
                    <td><span class="badge bg-success">${escapeHtml(m.status)}</span></td>
                </tr>
            `).join('');
        }
    } catch (e) {
        console.error(e);
    }
}

async function planMigrationDemo() {
    const table = document.getElementById('tableNameInput').value.trim();
    const op = document.getElementById('operationSelect').value;
    const field = document.getElementById('fieldNameInput').value.trim();
    const type = document.getElementById('dataTypeInput').value.trim();

    try {
        const res = await apiFetch('/database/migration/plan', {
            method: 'POST',
            body: JSON.stringify({
                table_name: table,
                operation: op,
                params: {
                    column_name: field,
                    column_type: type,
                    index_name: `idx_${table}_${field}`,
                    columns: [field],
                    nullable: true
                }
            })
        });

        if (res && res.success) {
            currentPlan = res.data;
            document.getElementById('metricRisk').innerText = `${currentPlan.risk_level} (${currentPlan.strategy})`;

            document.getElementById('planOutputBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[PLAN CREATED] Strategy: ${currentPlan.strategy}</div>
                <div class="text-white text-xs mb-1 font-monospace"><strong>Forward DDL:</strong> ${escapeHtml(currentPlan.forward_ddl)}</div>
                <div class="text-muted text-xs mb-2 font-monospace"><strong>Rollback DDL:</strong> ${escapeHtml(currentPlan.reverse_ddl)}</div>
                <button class="btn btn-xs btn-success w-100 fw-bold" onclick="applyCurrentPlan()">
                    <i class="bi bi-check-circle me-1"></i> Apply Migration to Database
                </button>
            `;

            if (typeof showToast === 'function') {
                showToast(`DDL Plan generated: ${currentPlan.strategy}`, 'info');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Planning error: ' + e.message, 'error');
    }
}

async function applyCurrentPlan() {
    if (!currentPlan) return;

    try {
        const res = await apiFetch('/database/migration/execute', {
            method: 'POST',
            body: JSON.stringify({ plan: currentPlan })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') showToast('Migration applied to audit ledger!', 'success');
            loadHistory();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Execution error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadHistory();
    planMigrationDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
