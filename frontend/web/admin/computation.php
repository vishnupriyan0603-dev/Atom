<?php
// ATOM Web Admin — Phase 31: Mathematical, Algorithmic & Symbolic Computation Dashboard
$pageTitle = "Mathematical & Algorithmic Computation";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F59E0B;">Mathematical &amp; Algorithmic Engine</h2>
        <p class="text-muted small mb-0">Exact symbolic equation solving, linear algebra matrices, statistical regression &amp; Big-O complexity analysis</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-dark fw-bold" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); border: none;" onclick="runQuickComputeDemo()">
            <i class="bi bi-calculator me-1"></i> Run Math Diagnostics
        </button>
    </div>
</div>

<!-- Computation Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SYMBOLIC SOLVER</div>
            <div class="fs-4 fw-bold text-warning" id="metricSolver">ACTIVE (Exact)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">LINEAR ALGEBRA</div>
            <div class="fs-4 fw-bold text-info" id="metricMatrix">50x50 CAP</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STATISTICAL ENGINE</div>
            <div class="fs-4 fw-bold text-success" id="metricStats">OLS / Pearson</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">BIG-O ANALYZER</div>
            <div class="fs-4 fw-bold" style="color:#F59E0B;" id="metricComplexity">AST-Based</div>
        </div>
    </div>
</div>

<!-- Computation Labs Grid -->
<div class="row g-4 mb-4">
    <!-- 1. Symbolic Equation Solver -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-warning"><i class="bi bi-superscript me-2"></i>Symbolic Equation Solver</span>
                <span class="badge bg-warning text-dark" id="solverBadge">READY</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ALGEBRAIC EQUATION</label>
                    <div class="input-group">
                        <input type="text" id="eqInput" class="form-control bg-black text-white border-secondary" value="2x^2 - 8x + 6 = 0" placeholder="e.g. 3x + 9 = 24 or x^2 - 5x + 6 = 0">
                        <button class="btn btn-warning text-dark fw-bold" onclick="solveEquation()">Solve</button>
                    </div>
                </div>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 180px;">
                    <div class="text-muted small fw-bold mb-2">STEP-BY-STEP DERIVATION:</div>
                    <div id="solverOutput" class="small text-emerald-400" style="font-family: monospace; white-space: pre-wrap; color:#34D399;">
Click 'Solve' to parse and compute exact algebraic roots.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Matrix & Linear Algebra Lab -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-grid-3x3 me-2"></i>Linear Algebra &amp; Matrix Lab</span>
                <span class="badge bg-info text-dark">2x2 &amp; 3x3</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">MATRIX A (JSON 2D Array)</label>
                    <input type="text" id="matrixInput" class="form-control bg-black text-white border-secondary mb-2" value="[[4, 7], [2, 6]]">
                    <div class="btn-group w-100">
                        <button class="btn btn-sm btn-outline-info" onclick="computeMatrix('determinant')">Determinant</button>
                        <button class="btn btn-sm btn-outline-info" onclick="computeMatrix('invert')">Invert [A^-1]</button>
                        <button class="btn btn-sm btn-outline-info" onclick="computeMatrix('transpose')">Transpose [A^T]</button>
                    </div>
                </div>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 140px;">
                    <div class="text-muted small fw-bold mb-2">MATRIX RESULT:</div>
                    <div id="matrixOutput" class="small text-info" style="font-family: monospace; white-space: pre-wrap;">
Ready for matrix operations.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Statistical Analyzer -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-bar-chart-line me-2"></i>Statistical &amp; Regression Analyzer</span>
                <span class="badge bg-success">DESCRIPTIVE / OLS</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DATASET (CSV Values)</label>
                    <div class="input-group">
                        <input type="text" id="statsInput" class="form-control bg-black text-white border-secondary" value="12, 18, 23, 29, 31, 42, 49, 58, 64">
                        <button class="btn btn-success fw-bold" onclick="computeStatistics()">Analyze</button>
                    </div>
                </div>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 180px;">
                    <div class="text-muted small fw-bold mb-2">STATISTICAL SUMMARY:</div>
                    <div id="statsOutput" class="small text-white" style="font-family: monospace; white-space: pre-wrap;">
Click 'Analyze' to compute mean, median, standard deviation, and IQR.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Algorithm Big-O Complexity Analyzer -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#F59E0B;"><i class="bi bi-speedometer2 me-2"></i>Algorithm Big-O Complexity Inspector</span>
                <span class="badge bg-warning text-dark" id="complexityBadge">AST METRICS</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CODE SNIPPET</label>
                    <textarea id="codeComplexityInput" class="form-control bg-black text-white border-secondary" rows="3" style="font-family: monospace; font-size: 12px;">for ($i = 0; $i < $n; $i++) {
    for ($j = 0; $j < $n; $j++) {
        $matrix[$i][$j] = $i * $j;
    }
}</textarea>
                </div>
                <button class="btn btn-sm btn-outline-warning w-100 mb-3" onclick="analyzeComplexity()">
                    <i class="bi bi-cpu me-1"></i> Analyze Time &amp; Space Complexity
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 120px;">
                    <div class="text-muted small fw-bold mb-2">COMPLEXITY PROFILE:</div>
                    <div id="complexityOutput" class="small" style="font-family: monospace; color:#F59E0B; white-space: pre-wrap;">
Ready for AST inspection.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function solveEquation() {
    const eq = document.getElementById('eqInput').value;
    document.getElementById('solverBadge').innerText = 'SOLVING...';
    try {
        const res = await fetch('/api/v1/compute/solve', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({equation: eq})
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('solverBadge').innerText = 'SOLVED';
            const output = `STATUS: ${data.data.status.toUpperCase()}\n` +
                           `SOLUTIONS: [ ${data.data.solutions.join(', ')} ]\n\n` +
                           `DERIVATION STEPS:\n` +
                           data.data.steps.map((s, idx) => `  ${idx + 1}. ${s}`).join('\n');
            document.getElementById('solverOutput').innerText = output;
        } else {
            document.getElementById('solverBadge').innerText = 'ERROR';
            document.getElementById('solverOutput').innerText = 'Error: ' + data.message;
        }
    } catch (e) {
        document.getElementById('solverOutput').innerText = 'Network error: ' + e.message;
    }
}

async function computeMatrix(op) {
    try {
        const matrixA = JSON.parse(document.getElementById('matrixInput').value);
        const res = await fetch('/api/v1/compute/matrix', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({operation: op, matrix_a: matrixA})
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('matrixOutput').innerText = JSON.stringify(data.data, null, 2);
        } else {
            document.getElementById('matrixOutput').innerText = 'Error: ' + data.message;
        }
    } catch (e) {
        document.getElementById('matrixOutput').innerText = 'Invalid JSON: ' + e.message;
    }
}

async function computeStatistics() {
    const raw = document.getElementById('statsInput').value;
    const nums = raw.split(',').map(s => parseFloat(s.trim())).filter(n => !isNaN(n));
    try {
        const res = await fetch('/api/v1/compute/statistics', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({mode: 'describe', data: nums})
        });
        const data = await res.json();
        if (data.success) {
            const d = data.data;
            const text = `Count: ${d.count} | Sum: ${d.sum}\n` +
                         `Mean: ${d.mean} | Median: ${d.median}\n` +
                         `Range: [${d.min} .. ${d.max}] (Δ: ${d.range})\n` +
                         `Variance: ${d.variance} | StdDev: ${d.std_dev}\n` +
                         `Percentiles: P25=${d.p25}, P75=${d.p75}, IQR=${d.iqr}`;
            document.getElementById('statsOutput').innerText = text;
        }
    } catch (e) {
        document.getElementById('statsOutput').innerText = 'Error: ' + e.message;
    }
}

async function analyzeComplexity() {
    const code = document.getElementById('codeComplexityInput').value;
    try {
        const res = await fetch('/api/v1/compute/complexity', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({code: code})
        });
        const data = await res.json();
        if (data.success) {
            const d = data.data;
            const text = `TIME COMPLEXITY  : ${d.time_complexity}\n` +
                         `SPACE COMPLEXITY : ${d.space_complexity}\n` +
                         `MAX NESTING      : ${d.max_loop_nesting} levels\n\n` +
                         `ANALYSIS:\n` +
                         d.reasons.map(r => ` • ${r}`).join('\n') +
                         (d.optimizations.length ? `\n\nOPTIMIZATIONS:\n` + d.optimizations.map(o => ` → ${o}`).join('\n') : '');
            document.getElementById('complexityOutput').innerText = text;
            document.getElementById('complexityBadge').innerText = d.time_complexity;
        }
    } catch (e) {
        document.getElementById('complexityOutput').innerText = 'Error: ' + e.message;
    }
}

function runQuickComputeDemo() {
    solveEquation();
    computeStatistics();
    analyzeComplexity();
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
