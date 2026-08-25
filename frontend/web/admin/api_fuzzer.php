<?php
// ATOM Web Admin — Phase 74: Autonomous API Schema Fuzzer & Zero-Day Vulnerability Scanner
$pageTitle = "API Schema Fuzzer (Phase 74)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F43F5E;">Autonomous API Schema Fuzzer &amp; Vulnerability Scanner</h2>
        <p class="text-muted small mb-0">Phase 74: Dynamic Boundary Mutation (SQLi, XSS, Type Juggling, Integer Overflow) &amp; Automated Endpoint Hardening</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-rose text-white fw-bold" style="background: #F43F5E;" onclick="runEndpointFuzzScan()">
            <i class="bi bi-shield-shaded me-1"></i> Run Fuzzing Scan
        </button>
    </div>
</div>

<!-- Fuzzer Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ENDPOINT ROBUSTNESS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricScore" style="color: #34D399;">100% (ROBUST)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MUTATION VECTORS TESTED</div>
            <div class="fs-4 fw-bold text-info" id="metricTestsCount">24 MUTATIONS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">VULNERABILITIES FOUND</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricVulns">0 DETECTED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SCANNER ENGINE</div>
            <div class="fs-4 fw-bold text-rose-400" style="color: #F43F5E;">Zero-Trust AST</div>
        </div>
    </div>
</div>

<!-- Fuzzing Target Sandbox & Results Matrix -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-rose-400"><i class="bi bi-bug-fill me-2"></i>Configure Endpoint Fuzz Target</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TARGET REST ENDPOINT</label>
                    <input type="text" id="targetEndpointInput" class="form-control bg-black text-white border-secondary small" value="/api/users/profile" placeholder="/api/...">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PARAMETER SCHEMA (JSON)</label>
                    <textarea id="schemaParamsInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="4">{"user_id": "int", "email": "string", "tenant_id": "string"}</textarea>
                </div>

                <button class="btn btn-sm text-white fw-bold w-100" style="background: #F43F5E;" onclick="runEndpointFuzzScan()">
                    <i class="bi bi-radioactive me-1"></i> Fuzz All Mutation Vectors
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-shield-check me-2"></i>Fuzzing Mutation Audit Log</span>
                <span class="badge bg-success" id="scanBadge">ALL MUTATIONS SAFE</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Param</th>
                                <th>Vector</th>
                                <th>Payload</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="fuzzTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Click 'Run Fuzzing Scan' to start...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function runEndpointFuzzScan() {
    const endpoint = document.getElementById('targetEndpointInput').value.trim();
    let params = {};
    try {
        params = JSON.parse(document.getElementById('schemaParamsInput').value);
    } catch(e) {
        params = { id: 'int', query: 'string' };
    }

    try {
        const res = await apiFetch('/testing/fuzzer/scan', {
            method: 'POST',
            body: JSON.stringify({ endpoint: endpoint, params: params })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricScore').innerText = `${d.robustness_score}%`;
            document.getElementById('metricTestsCount').innerText = `${d.total_mutations_tested} MUTATIONS`;
            document.getElementById('metricVulns').innerText = `${d.vulnerabilities_found} DETECTED`;

            renderFuzzLog(d.test_runs || []);
            if (typeof showToast === 'function') showToast(`Fuzz scan complete: ${d.total_mutations_tested} mutations tested`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Fuzz scan error: ' + e.message, 'error');
    }
}

function renderFuzzLog(runs) {
    const tbody = document.getElementById('fuzzTableBody');
    if (!runs || runs.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">No mutation runs recorded.</td></tr>`;
        return;
    }

    tbody.innerHTML = runs.map(r => `
        <tr>
            <td class="fw-bold text-white">${escapeHtml(r.param)}</td>
            <td><span class="badge bg-secondary">${escapeHtml(r.category)}</span></td>
            <td class="font-monospace text-xs text-muted">${escapeHtml(r.payload)}</td>
            <td><span class="badge ${r.is_vulnerable ? 'bg-danger' : 'bg-success'}">${r.is_vulnerable ? 'VULNERABLE' : 'PASSED'}</span></td>
        </tr>
    `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    runEndpointFuzzScan();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
