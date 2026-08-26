<?php
// ATOM Web Admin — Phase 93: Autonomous Multi-Source Data Pipeline Orchestrator & Stream ETL Transformer
$pageTitle = "Data Pipeline & Stream ETL (Phase 93)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F59E0B;">Data Pipeline Orchestrator &amp; Stream ETL</h2>
        <p class="text-muted small mb-0">Phase 93: Multi-Stage Stream DAG ($Extract \rightarrow Transform \rightarrow Filter \rightarrow Sink$), Data Normalization &amp; Dead-Record Quarantine</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-warning text-dark fw-bold" onclick="runEtlPipelineDemo()">
            <i class="bi bi-play-circle-fill me-1"></i> Execute Pipeline
        </button>
    </div>
</div>

<!-- ETL Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE PIPELINES</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricPipelines" style="color: #FBBF24;">2 PIPELINES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">RECORDS INGESTED</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricIngested" style="color: #34D399;">3 RECORDS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EXECUTION SPEED</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricExecTime">0.18 ms</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">QUARANTINE SAFETY</div>
            <div class="fs-4 fw-bold text-info">Zero-Drop Guarantee</div>
        </div>
    </div>
</div>

<!-- Ingestion Records & Output Inspector -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-amber-400"><i class="bi bi-input-cursor-text me-2"></i>Raw Ingestion Stream</span>
                <select id="pipelineSelect" class="form-select form-select-sm bg-black text-white border-secondary w-auto">
                    <option value="user_activity_sanitizer" selected>User Activity Sanitizer</option>
                    <option value="financial_transaction_enricher">Financial Transaction Enricher</option>
                </select>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">INPUT RECORDS (JSON ARRAY)</label>
                    <textarea id="rawRecordsInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="8">[
  {"id": 101, "email": "  JOHN.DOE@CORP.IO  ", "active": true, "amount": 145.50},
  {"id": 102, "email": "inactive_user@test.org", "active": false, "amount": 5.00},
  {"id": 103, "email": "Sarah_Connor@Cyberdyne.Com", "active": true, "amount": 250.00}
]</textarea>
                </div>

                <button class="btn btn-sm btn-warning text-dark fw-bold w-100" onclick="runEtlPipelineDemo()">
                    <i class="bi bi-gear-fill me-1"></i> Transform &amp; Ingest to Data Sink
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-cyan-400"><i class="bi bi-database-check me-2"></i>Transformed Data Sink</span>
                <span class="badge bg-secondary" id="sinkBadge">EMITTED: 2</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary text-xs font-monospace text-emerald-400" id="etlOutputBox" style="max-height: 220px; overflow-y: auto;">
                    [Ready] Click 'Transform &amp; Ingest to Data Sink' to run ETL pipeline...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function runEtlPipelineDemo() {
    const pipeline = document.getElementById('pipelineSelect').value;
    let records = [];
    try {
        records = JSON.parse(document.getElementById('rawRecordsInput').value);
    } catch (e) {
        if (typeof showToast === 'function') showToast('Invalid JSON records array', 'error');
        return;
    }

    try {
        const res = await apiFetch('/database/etl/execute', {
            method: 'POST',
            body: JSON.stringify({ records: records, pipeline_id: pipeline })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricIngested').innerText = `${d.ingested_count} RECORDS`;
            document.getElementById('metricExecTime').innerText = `${d.execution_time_ms} ms`;
            document.getElementById('sinkBadge').innerText = `EMITTED: ${d.emitted_count} (FILTERED: ${d.filtered_count})`;

            document.getElementById('etlOutputBox').innerText = JSON.stringify(d.records, null, 2);

            if (typeof showToast === 'function') {
                showToast(`ETL: Emitted ${d.emitted_count} records in ${d.execution_time_ms}ms`, 'success');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('ETL error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    runEtlPipelineDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
