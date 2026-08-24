<?php
// ATOM Web Admin — Phase 35: Autonomous Code Refactoring & Micro-Architecture Evolution Engine Dashboard
$pageTitle = "Code Refactoring Studio";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Autonomous Code Refactoring Studio</h2>
        <p class="text-muted small mb-0">AST code smell detection, automated safe transformations, architectural coupling &amp; circular dependency analysis</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%); border: none;" onclick="runDefaultRefactorDemo()">
            <i class="bi bi-magic me-1"></i> Run Refactor Demo
        </button>
    </div>
</div>

<!-- Refactoring Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SMELL SCANNER</div>
            <div class="fs-4 fw-bold text-success" id="metricScanner">ACTIVE (Zero Delay)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MAINTAINABILITY INDEX</div>
            <div class="fs-4 fw-bold" style="color:#EC4899;" id="metricMI">92.4 / 100</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CYCLOMATIC COMPLEXITY</div>
            <div class="fs-4 fw-bold text-info" id="metricComplexity">M = 3 (Healthy)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SAFETY VERIFIER</div>
            <div class="fs-4 fw-bold text-warning" id="metricVerifier">INVARIANTS LOCKED</div>
        </div>
    </div>
</div>

<!-- Interactive Refactoring Grid -->
<div class="row g-4 mb-4">
    <!-- 1. Code Smell Scanner -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#EC4899;"><i class="bi bi-bug me-2"></i>Static Code Smell Detector</span>
                <span class="badge bg-secondary" id="urgencyBadge">URGENCY: LOW</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SOURCE CODE TO ANALYZE</label>
                    <textarea id="smellCodeInput" class="form-control bg-black text-white border-secondary" rows="8" style="font-family: monospace; font-size: 13px;">class OrderProcessor {
    public function process($order) {
        if ($order->isValid() === true) {
            if ($order->hasStock() === true) {
                if ($order->hasBalance() === true) {
                    $order->complete();
                    return true;
                }
            }
        }
        return false;
    }
}</textarea>
                </div>
                <button class="btn btn-sm text-white fw-bold w-100 mb-3" style="background: #EC4899;" onclick="scanCodeSmells()">
                    <i class="bi bi-search me-1"></i> Scan for Code Smells &amp; Metrics
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 120px;">
                    <div class="text-muted small fw-bold mb-1">DETECTION RESULTS:</div>
                    <div id="smellOutput" class="small text-pink-300" style="font-family: monospace; white-space: pre-wrap; color: #F472B6;">
Click 'Scan for Code Smells' to analyze AST complexity.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. AST Transformation Playground -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-gear-wide-connected me-2"></i>AST Transformation Playground</span>
                <span class="badge bg-info text-dark" id="transformBadge">AST READY</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold mb-0">TRANSFORMATION TYPE</label>
                        <select id="transformType" class="form-select form-select-sm bg-black text-white border-secondary">
                            <option value="simplify_boolean">Simplify Boolean Expressions</option>
                            <option value="decompose_conditional">Decompose Conditional (Guard Clauses)</option>
                            <option value="rename_symbol">Rename Symbol Safely</option>
                        </select>
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <button class="btn btn-sm btn-info text-dark fw-bold w-100" onclick="applyTransform()">
                            <i class="bi bi-lightning-fill me-1"></i> Apply AST Transform
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TRANSFORMED CODE DIFF PREVIEW</label>
                    <textarea id="transformedCodeView" class="form-control bg-black text-white border-secondary" rows="8" readonly style="font-family: monospace; font-size: 13px; color: #34D399;">// Transformed code will appear here with preserved API invariants...</textarea>
                </div>
                <div id="verifyStatus" class="small text-muted" style="font-family: monospace;">
                    Safety Invariants: Verified &amp; Balanced
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function scanCodeSmells() {
    const code = document.getElementById('smellCodeInput').value;
    try {
        const res = await fetch('/api/v1/refactor/smells', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ code: code })
        });
        const data = await res.json();
        if (data.success) {
            const d = data.data;
            document.getElementById('metricMI').innerText = `${d.maintainability_index} / 100`;
            document.getElementById('metricComplexity').innerText = `M = ${d.cyclomatic_complexity}`;
            document.getElementById('urgencyBadge').innerText = `URGENCY: ${d.refactoring_urgency}`;
            document.getElementById('urgencyBadge').className = `badge bg-${d.refactoring_urgency === 'HIGH' ? 'danger' : (d.refactoring_urgency === 'MEDIUM' ? 'warning' : 'success')}`;
            
            document.getElementById('smellOutput').innerText = 
                `MAINTAINABILITY INDEX : ${d.maintainability_index} / 100\n` +
                `CYCLOMATIC COMPLEXITY : ${d.cyclomatic_complexity}\n` +
                `TOTAL CODE SMELLS     : ${d.total_smells}\n` +
                `LINES OF CODE (LOC)   : ${d.loc}\n` +
                `REFACTORING URGENCY   : ${d.refactoring_urgency}\n\n` +
                (d.smells.length > 0 ? d.smells.map(s => `• [${s.severity}] ${s.type}: ${s.description}`).join('\n') : '✨ Code is clean and well-structured!');
        }
    } catch (e) {
        document.getElementById('smellOutput').innerText = 'Error: ' + e.message;
    }
}

async function applyTransform() {
    const code = document.getElementById('smellCodeInput').value;
    const type = document.getElementById('transformType').value;
    try {
        const res = await fetch('/api/v1/refactor/transform', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                type: type,
                code: code,
                options: {
                    old_name: '$order',
                    new_name: '$purchaseOrder'
                }
            })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('transformedCodeView').value = data.data.code;
            document.getElementById('verifyStatus').innerHTML = `<span class="text-success fw-bold">✔ ${data.data.description} (Syntax Verified Safe)</span>`;
        } else {
            document.getElementById('transformedCodeView').value = '// Error: ' + data.error;
        }
    } catch (e) {
        document.getElementById('transformedCodeView').value = '// Error: ' + e.message;
    }
}

function runDefaultRefactorDemo() {
    scanCodeSmells();
    setTimeout(applyTransform, 300);
}

document.addEventListener('DOMContentLoaded', () => scanCodeSmells());
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
