<?php
// ATOM Web Admin — Phase 40: Autonomous Self-Healing Infrastructure & Incident Response Dashboard
$pageTitle = "Incident Response & Self-Healing";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EF4444;">Autonomous Self-Healing &amp; Incident Response</h2>
        <p class="text-muted small mb-0">Automated runbook remediation playbooks, 3-state circuit breakers, SEV incident classification &amp; RCA post-mortem generation</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); border: none;" onclick="runIncidentDemo()">
            <i class="bi bi-shield-fill-check me-1"></i> Trigger Auto-Healing
        </button>
    </div>
</div>

<!-- Incident Response Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CIRCUIT BREAKER</div>
            <div class="fs-4 fw-bold text-success" id="metricCB">CLOSED (Healthy)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AUTO-HEALING STATUS</div>
            <div class="fs-4 fw-bold text-info" id="metricHealing">ACTIVE (Autonomous)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">INCIDENT SEVERITY</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricSev" style="color: #10B981;">SEV0 (Nominal)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">RCA GENERATOR</div>
            <div class="fs-4 fw-bold text-warning" id="metricRCA">Automated (Ready)</div>
        </div>
    </div>
</div>

<!-- Interactive Incident Response Grid -->
<div class="row g-4 mb-4">
    <!-- 1. Incident Classifier & Runbook Remediation -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#EF4444;"><i class="bi bi-exclamation-triangle-fill me-2"></i>Incident Event &amp; Runbook Orchestrator</span>
                <span class="badge bg-danger text-white" id="sevBadge">SEV2_MAJOR</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TELEMETRY ERROR MESSAGE</label>
                    <input type="text" id="eventMsgInput" class="form-control bg-black text-white border-secondary" value="database connection refused after pool timeout">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">ERROR RATE (%)</label>
                        <input type="number" id="errorRateInput" class="form-control bg-black text-white border-secondary" value="28.5">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">LATENCY (MS)</label>
                        <input type="number" id="latencyInput" class="form-control bg-black text-white border-secondary" value="4500">
                    </div>
                </div>
                <button class="btn btn-sm text-white fw-bold w-100 mb-3" style="background: #EF4444;" onclick="classifyAndHeal()">
                    <i class="bi bi-lightning-charge-fill me-1"></i> Classify Severity &amp; Execute Runbook
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 120px;">
                    <div class="text-muted small fw-bold mb-1">REMEDIATION STATUS:</div>
                    <div id="remediationOutput" class="small text-red-300" style="font-family: monospace; white-space: pre-wrap; color: #FCA5A5;">
Ready to classify incidents and execute self-healing actions.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Circuit Breaker & Post-Mortem RCA Studio -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-warning"><i class="bi bi-file-earmark-text-fill me-2"></i>Post-Mortem Root Cause Analysis (RCA)</span>
                <span class="badge bg-warning text-dark">AUTO-GENERATED</span>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-sm btn-outline-danger w-50 fw-bold" onclick="simulateCircuitTrip()">
                        <i class="bi bi-x-circle me-1"></i> Simulate Failure
                    </button>
                    <button class="btn btn-sm btn-outline-success w-50 fw-bold" onclick="simulateCircuitSuccess()">
                        <i class="bi bi-check-circle me-1"></i> Record Recovery
                    </button>
                </div>
                <button class="btn btn-sm btn-warning text-dark fw-bold w-100 mb-3" onclick="generatePostMortem()">
                    <i class="bi bi-journal-text me-1"></i> Generate RCA Post-Mortem Document
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 140px;">
                    <div class="text-muted small fw-bold mb-1">POST-MORTEM PREVIEW:</div>
                    <div id="postMortemOutput" class="small text-warning" style="font-family: monospace; white-space: pre-wrap;">
Click 'Generate RCA Post-Mortem Document' to preview report.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function classifyAndHeal() {
    const msg = document.getElementById('eventMsgInput').value;
    const errRate = parseFloat(document.getElementById('errorRateInput').value);
    const latency = parseFloat(document.getElementById('latencyInput').value);

    try {
        const cRes = await fetch('/api/v1/incident/classify', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ message: msg, error_rate: errRate, latency_ms: latency })
        });
        const cData = await cRes.json();
        if (cData.success) {
            const inc = cData.data;
            document.getElementById('sevBadge').innerText = inc.severity;
            document.getElementById('metricSev').innerText = inc.severity;

            // Trigger recommended runbook
            const rRes = await fetch('/api/v1/incident/remediate', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ runbook: inc.recommended_action, subsystem: inc.subsystem })
            });
            const rData = await rRes.json();
            if (rData.success) {
                const run = rData.data;
                document.getElementById('remediationOutput').innerText = 
                    `INCIDENT ID : ${inc.incident_id}\n` +
                    `SEVERITY    : ${inc.severity}\n` +
                    `RUNBOOK     : ${run.runbook} (${run.status})\n` +
                    `DURATION    : ${run.duration_ms} ms\n\n` +
                    `ACTIONS TAKEN:\n` +
                    run.steps_taken.map(s => `  ✔ ${s}`).join('\n');
            }
        }
    } catch (e) {
        document.getElementById('remediationOutput').innerText = 'Error: ' + e.message;
    }
}

async function simulateCircuitTrip() {
    try {
        const res = await fetch('/api/v1/incident/circuit/record', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ success: false })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('metricCB').innerText = `${data.data.state} (Failures: ${data.data.failure_count})`;
            document.getElementById('metricCB').className = 'fs-4 fw-bold text-danger';
        }
    } catch (_) {}
}

async function simulateCircuitSuccess() {
    try {
        const res = await fetch('/api/v1/incident/circuit/record', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ success: true })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('metricCB').innerText = 'CLOSED (Healthy)';
            document.getElementById('metricCB').className = 'fs-4 fw-bold text-success';
        }
    } catch (_) {}
}

async function generatePostMortem() {
    try {
        const res = await fetch('/api/v1/incident/postmortem', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                incident_id: 'inc_auto_804',
                severity: 'SEV2_MAJOR',
                subsystem: 'database_pool',
                root_cause: 'Unindexed high-volume query exhausted database pool worker sockets',
                downtime_minutes: 2.8
            })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('postMortemOutput').innerText = data.data.post_mortem_md;
        }
    } catch (e) {
        document.getElementById('postMortemOutput').innerText = 'Error: ' + e.message;
    }
}

function runIncidentDemo() {
    classifyAndHeal();
    generatePostMortem();
}

document.addEventListener('DOMContentLoaded', () => generatePostMortem());
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
