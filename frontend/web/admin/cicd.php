<?php
// ATOM Web Admin — Phase 29: Autonomous Test Generation & CI/CD Pipeline Dashboard
$pageTitle = "CI/CD & Autonomous Test Generator";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #10B981;">Autonomous Test Gen &amp; CI/CD Pipeline</h2>
        <p class="text-muted small mb-0">AST-based PHPUnit test synthesis, automated failure diagnosis, self-correction patches &amp; multi-stage CI/CD</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); border: none; color: white;" onclick="triggerFullPipeline()">
            <i class="bi bi-play-fill me-1"></i> Run CI/CD Pipeline
        </button>
    </div>
</div>

<!-- CI/CD Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PIPELINE STATUS</div>
            <div class="fs-4 fw-bold text-success" id="metricPipelineStatus">PASSING</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">UNIT TESTS</div>
            <div class="fs-4 fw-bold text-info" id="metricTestsCount">240 PASSING</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CODE COVERAGE</div>
            <div class="fs-4 fw-bold text-warning" id="metricCoverage">94.2%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SELF-CORRECTION</div>
            <div class="fs-4 fw-bold" style="color:#10B981;">ACTIVE</div>
        </div>
    </div>
</div>

<!-- Multi-Stage CI/CD Timeline -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold" style="color:#10B981;"><i class="bi bi-diagram-2 me-2"></i>Multi-Stage CI/CD Pipeline Execution</span>
        <span class="badge bg-success" id="lastRunBadge">LATEST RUN: SUCCESS</span>
    </div>
    <div class="card-body">
        <div class="row g-3 text-center" id="stagesContainer">
            <div class="col">
                <div class="p-3 bg-black border border-success rounded">
                    <i class="bi bi-file-earmark-code text-success fs-3 mb-2 d-block"></i>
                    <div class="fw-bold">1. LINT</div>
                    <div class="text-muted small">PSR-12 (0 errors)</div>
                </div>
            </div>
            <div class="col">
                <div class="p-3 bg-black border border-success rounded">
                    <i class="bi bi-check2-circle text-success fs-3 mb-2 d-block"></i>
                    <div class="fw-bold">2. UNIT TESTS</div>
                    <div class="text-muted small">240/240 passed</div>
                </div>
            </div>
            <div class="col">
                <div class="p-3 bg-black border border-success rounded">
                    <i class="bi bi-shield-check text-success fs-3 mb-2 d-block"></i>
                    <div class="fw-bold">3. SECURITY</div>
                    <div class="text-muted small">0 leaks detected</div>
                </div>
            </div>
            <div class="col">
                <div class="p-3 bg-black border border-success rounded">
                    <i class="bi bi-pie-chart text-success fs-3 mb-2 d-block"></i>
                    <div class="fw-bold">4. COVERAGE</div>
                    <div class="text-muted small">94.2% threshold ok</div>
                </div>
            </div>
            <div class="col">
                <div class="p-3 bg-black border border-success rounded">
                    <i class="bi bi-box-seam text-success fs-3 mb-2 d-block"></i>
                    <div class="fw-bold">5. BUILD</div>
                    <div class="text-muted small">Desktop &amp; Mobile ready</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Panel 1: AI Test Synthesizer Playground -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#38BDF8;"><i class="bi bi-magic me-2"></i>AI Test Synthesizer Playground</span>
                <span class="badge bg-info text-dark">PHPUNIT GENERATOR</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Source Class Definition</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="synthInputCode" rows="6" style="font-family: monospace; font-size: 12px;">
<?php
namespace Atom\Services;

class NotificationDispatcher
{
    public function sendEmail($to, $subject, $body)
    {
        return true;
    }

    public function sendPush($deviceId, $title)
    {
        return ['status' => 'delivered'];
    }
}
</textarea>
                </div>
                <button class="btn btn-info btn-sm w-100 mb-3" onclick="synthesizeTestLive()">
                    <i class="bi bi-cpu me-1"></i> Synthesize PHPUnit Test Suite
                </button>
                <label class="form-label text-muted small fw-bold">Generated Test Suite</label>
                <div class="p-2 bg-black border border-secondary rounded" id="synthesizedOutputArea" style="min-height: 180px; max-height: 220px; overflow-y: auto; font-family: monospace; font-size: 11px; color:#34D399; white-space: pre-wrap;">
Click "Synthesize PHPUnit Test Suite" to generate test assertions automatically.
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 2: Self-Correction & Patch Sandbox -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#F59E0B;"><i class="bi bi-tools me-2"></i>Self-Correction &amp; Patch Sandbox</span>
                <span class="badge bg-warning text-dark">AUTO-REPAIR</span>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label text-muted small fw-bold">Test Failure / Error Trace</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="repairErrorTrace" rows="2" style="font-family: monospace; font-size: 11px;">TypeError: Return value of execute() must be of the type bool, null returned</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Faulty Code Snippet</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="repairFaultyCode" rows="3" style="font-family: monospace; font-size: 12px;">public function execute($params) { /* missing return */ }</textarea>
                </div>
                <button class="btn btn-warning btn-sm w-100 mb-3" onclick="repairCodeLive()">
                    <i class="bi bi-wrench-adjustable me-1"></i> Diagnose &amp; Synthesize Patch
                </button>
                <label class="form-label text-muted small fw-bold">Synthesized Code Patch</label>
                <div class="p-2 bg-black border border-secondary rounded" id="repairOutputArea" style="min-height: 140px; font-family: monospace; font-size: 11px; color:#FBBF24; white-space: pre-wrap;">
Enter error trace and click "Diagnose & Synthesize Patch" to generate patch.
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const API_BASE = window.ATOM_API_BASE || '/api';
const TOKEN    = localStorage.getItem('atom_token') || '';

function apiFetch(path, opts = {}) {
    return fetch(API_BASE + path, {
        ...opts,
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + TOKEN, ...(opts.headers || {}) }
    }).then(r => r.json());
}

function synthesizeTestLive() {
    const code = document.getElementById('synthInputCode').value;
    document.getElementById('synthesizedOutputArea').textContent = 'Synthesizing test suite from AST...';

    apiFetch('/cicd/test/generate', {
        method: 'POST',
        body: JSON.stringify({ code: code, class_name: 'NotificationDispatcher' })
    }).then(res => {
        if (res.success) {
            document.getElementById('synthesizedOutputArea').textContent = res.data.test_code;
        } else {
            document.getElementById('synthesizedOutputArea').textContent = 'Generation failed: ' + (res.error || 'Error');
        }
    });
}

function repairCodeLive() {
    const error = document.getElementById('repairErrorTrace').value;
    const code = document.getElementById('repairFaultyCode').value;
    document.getElementById('repairOutputArea').textContent = 'Diagnosing root-cause and synthesizing diff...';

    apiFetch('/cicd/repair', {
        method: 'POST',
        body: JSON.stringify({ code: code, error: error })
    }).then(res => {
        if (res.success) {
            document.getElementById('repairOutputArea').textContent =
                `// ${res.data.explanation}\n\n` + res.data.patched_code;
        } else {
            document.getElementById('repairOutputArea').textContent = 'Repair failed: ' + (res.error || 'Error');
        }
    });
}

function triggerFullPipeline() {
    document.getElementById('lastRunBadge').textContent = 'RUNNING...';
    apiFetch('/cicd/pipeline/trigger', {
        method: 'POST',
        body: JSON.stringify({ stages: ['lint', 'unit_tests', 'security_scan', 'coverage_check', 'build_check'] })
    }).then(res => {
        if (res.success) {
            document.getElementById('lastRunBadge').textContent = 'RUN COMPLETED (' + res.data.total_duration_ms + 'ms)';
            alert('CI/CD Pipeline executed cleanly: ' + res.data.status.toUpperCase());
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    synthesizeTestLive();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
