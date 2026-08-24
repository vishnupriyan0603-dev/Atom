<?php
// ATOM Web Admin — Phase 30: Autonomous Long-Horizon Planning & Graph-of-Thought Dashboard
$pageTitle = "Long-Horizon Planning & Graph-of-Thought (GoT)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">Long-Horizon Planning &amp; Graph-of-Thought</h2>
        <p class="text-muted small mb-0">Hierarchical DAG task decomposition, multi-branch MCTS/GoT search, real-time verification &amp; state rollback</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white" style="background: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%); border: none;" onclick="runQuickSearch()">
            <i class="bi bi-diagram-3 me-1"></i> Explore GoT Tree
        </button>
    </div>
</div>

<!-- Planning Engine Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PLANNING ENGINE</div>
            <div class="fs-4 fw-bold text-info" id="metricEngineStatus">ACTIVE (GoT/MCTS)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MAX SEARCH DEPTH</div>
            <div class="fs-4 fw-bold text-primary" id="metricMaxDepth">5 TIERS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AVG NODE CONFIDENCE</div>
            <div class="fs-4 fw-bold text-success" id="metricConfidence">94.8%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AUTO-BACKTRACKING</div>
            <div class="fs-4 fw-bold text-warning" id="metricBacktrack">ENABLED</div>
        </div>
    </div>
</div>

<!-- Goal Decomposition & Search Controls -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-info"><i class="bi bi-input-cursor me-2"></i>Goal Decomposition &amp; Thought Search Explorer</span>
        <span class="badge bg-secondary" id="treeStatusBadge">READY</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-7">
                <label class="form-label text-muted small fw-bold">OBJECTIVE / GOAL</label>
                <input type="text" id="goalInput" class="form-control bg-black text-white border-secondary" placeholder="e.g. Build an autonomous telemetry microservice with security scanning and rate limiting" value="Build an autonomous telemetry microservice with security scanning and rate limiting">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-bold">BRANCHING FACTOR</label>
                <select id="branchingFactor" class="form-select bg-black text-white border-secondary">
                    <option value="2">2 Branches</option>
                    <option value="3" selected>3 Branches</option>
                    <option value="4">4 Branches</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold">MAX DEPTH</label>
                <select id="maxDepth" class="form-select bg-black text-white border-secondary">
                    <option value="2">2 Levels</option>
                    <option value="3" selected>3 Levels</option>
                    <option value="4">4 Levels</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-info text-white" onclick="decomposeGoal()">
                <i class="bi bi-diagram-2 me-1"></i> Decompose into Hierarchical DAG
            </button>
            <button class="btn btn-sm btn-primary" onclick="searchGoT()">
                <i class="bi bi-tree me-1"></i> Multi-Branch GoT Search
            </button>
            <button class="btn btn-sm btn-outline-warning" onclick="simulateStepExecution(true)">
                <i class="bi bi-check-circle me-1"></i> Execute &amp; Verify Step
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="simulateStepExecution(false)">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Simulate Failure &amp; Backtrack
            </button>
        </div>
    </div>
</div>

<!-- Tree Representation & Node Trajectory -->
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-diagram-3-fill me-2"></i>Graph-of-Thought Hierarchy / ASCII Tree</span>
                <span class="badge bg-info" id="nodeCountBadge">0 NODES</span>
            </div>
            <div class="card-body p-0">
                <pre id="treeDisplay" class="bg-black text-white p-3 mb-0" style="font-family: monospace; font-size: 12px; max-height: 400px; overflow: auto; border: none;">
● node_root: Root Objective: Build an autonomous telemetry microservice [100%] (selected)
│   ├── ◆ node_1: Research Context & Requirements [88%] (evaluated)
│   │   ├── ● node_1_1: Inspect workspace & telemetry configs [92%] (selected)
│   │   └── ⨯ node_1_2: Brute force codebase crawl [25%] (pruned)
│   ├── ● node_2: Target Implementation & Middleware [94%] (selected)
│   │   ├── ● node_2_1: Write TelemetryEngine.php [96%] (selected)
│   │   └── ○ node_2_2: Alternative monolithic handler [70%] (exploring)
│   └── ◆ node_3: Unit Testing & Verification Pass [95%] (evaluated)
                </pre>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-info"><i class="bi bi-info-circle me-2"></i>Execution Trajectory &amp; Snapshot</span>
            </div>
            <div class="card-body p-0">
                <pre id="trajectoryLog" class="bg-black text-emerald-400 p-3 mb-0" style="font-family: monospace; font-size: 11px; max-height: 400px; overflow: auto; color: #34D399; border: none;">
[23:46:00] [GoT] Engine initialized with Monte Carlo Tree Search.
[23:46:01] [Decomposer] Generated 4 DAG milestones with topological ordering.
[23:46:02] [MCTS] Expanded node_root with 3 candidate hypotheses.
[23:46:03] [Evaluator] Hypothesis 1 evaluated (score: 0.92) -> status: evaluated.
[23:46:03] [Evaluator] Hypothesis 3 evaluated (score: 0.25) -> status: PRUNED.
[23:46:04] [MCTS] Optimal path selected: node_root -> node_1 -> node_1_1 -> node_2_1.
[23:46:05] [Verifier] Preconditions verified. Ready for step execution.
                </pre>
            </div>
        </div>
    </div>
</div>

<script>
let currentTreeId = 'got_tree_demo';
let activeNodeId = 'node_1_1';

async function decomposeGoal() {
    const goal = document.getElementById('goalInput').value;
    const maxDepth = document.getElementById('maxDepth').value;
    
    document.getElementById('treeStatusBadge').innerText = 'DECOMPOSING...';
    
    try {
        const res = await fetch('/api/v1/planning/decompose', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({goal: goal, max_depth: parseInt(maxDepth)})
        });
        const data = await res.json();
        if (data.success) {
            currentTreeId = data.data.tree_id;
            document.getElementById('treeDisplay').innerText = data.data.ascii_tree;
            document.getElementById('nodeCountBadge').innerText = `${data.data.total_nodes} NODES`;
            document.getElementById('treeStatusBadge').innerText = 'DAG DECOMPOSED';
            logEvent(`[DAG Decomposed] Tree ${currentTreeId} created with ${data.data.total_nodes} nodes.`);
        }
    } catch (e) {
        logEvent(`[Error] Decomposition failed: ${e.message}`);
    }
}

async function searchGoT() {
    const goal = document.getElementById('goalInput').value;
    const branching = document.getElementById('branchingFactor').value;
    const maxDepth = document.getElementById('maxDepth').value;
    
    document.getElementById('treeStatusBadge').innerText = 'SEARCHING GoT...';
    
    try {
        const res = await fetch('/api/v1/planning/search', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                goal: goal,
                branching_factor: parseInt(branching),
                max_depth: parseInt(maxDepth)
            })
        });
        const data = await res.json();
        if (data.success) {
            currentTreeId = data.data.tree_id;
            document.getElementById('treeDisplay').innerText = data.data.ascii_tree;
            document.getElementById('nodeCountBadge').innerText = `${data.data.total_nodes} NODES (${data.data.pruned_nodes} PRUNED)`;
            document.getElementById('treeStatusBadge').innerText = 'SEARCH COMPLETE';
            logEvent(`[GoT Search] Generated ${data.data.total_nodes} nodes. Optimal path: ${data.data.best_path.join(' -> ')}`);
        }
    } catch (e) {
        logEvent(`[Error] Search failed: ${e.message}`);
    }
}

async function simulateStepExecution(success) {
    if (!currentTreeId) {
        alert('Please run a search or decomposition first');
        return;
    }
    
    const mockOutput = success 
        ? { status: 'success', result: 'Step completed cleanly with 0 errors' }
        : { status: 'error', error: 'Fatal error: memory quota exceeded during branch expansion' };
        
    try {
        const res = await fetch('/api/v1/planning/execute-step', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                tree_id: currentTreeId,
                node_id: activeNodeId,
                output: mockOutput
            })
        });
        const data = await res.json();
        if (data.success) {
            if (data.data.verification.verified) {
                logEvent(`[Execution Verified] Node ${activeNodeId} executed cleanly. Checkpoint saved.`);
            } else {
                logEvent(`[Verification Failed] Flaw: ${data.data.verification.flaw}`);
                if (data.data.backtrack && data.data.backtrack.backtracked) {
                    logEvent(`[Auto-Backtrack] Reverted to ${data.data.backtrack.ancestor_id}, activated alternate branch: ${data.data.backtrack.next_branch_id}`);
                }
            }
        }
    } catch (e) {
        logEvent(`[Error] Execution simulator failed: ${e.message}`);
    }
}

function runQuickSearch() {
    searchGoT();
}

function logEvent(msg) {
    const time = new Date().toTimeString().split(' ')[0];
    const log = document.getElementById('trajectoryLog');
    log.innerText += `\n[${time}] ${msg}`;
    log.scrollTop = log.scrollHeight;
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
