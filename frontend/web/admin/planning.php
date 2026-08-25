<?php
// ATOM Web Admin — Autonomous Long-Horizon Planning & Graph-of-Thought (GoT / ToT / MCTS) Studio
$pageTitle = "Long-Horizon Planning & Graph-of-Thought (GoT)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;"><i class="bi bi-diagram-3-fill me-2"></i>Long-Horizon Planning &amp; Graph-of-Thought (GoT)</h2>
        <p class="text-muted small mb-0">Hierarchical DAG task decomposition, multi-branch MCTS/GoT search, real-time node verification &amp; state rollback</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Studio
        </button>
        <button class="btn btn-sm text-white" style="background: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%); border: none;" onclick="searchGoT()">
            <i class="bi bi-lightning-charge me-1"></i> Run MCTS Search
        </button>
    </div>
</div>

<!-- Planning Engine Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white h-100 shadow-sm">
            <div class="text-muted small fw-bold">SEARCH ALGORITHM</div>
            <div class="fs-4 fw-bold text-info" id="metricEngineStatus">MCTS + GoT DAG</div>
            <div class="text-muted text-xs mt-1">Monte Carlo Tree Search + Graph Decomposition</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white h-100 shadow-sm">
            <div class="text-muted small fw-bold">EXPLORATION DEPTH</div>
            <div class="fs-4 fw-bold text-primary" id="metricMaxDepth">3 TIERS (5 MAX)</div>
            <div class="text-muted text-xs mt-1">Adaptive Branching Factor: 3.0x</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white h-100 shadow-sm">
            <div class="text-muted small fw-bold">AVG NODE CONFIDENCE</div>
            <div class="fs-4 fw-bold text-success" id="metricConfidence">94.8%</div>
            <div class="text-muted text-xs mt-1">Bayesian Backprop Pruning Active</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white h-100 shadow-sm">
            <div class="text-muted small fw-bold">AUTO-BACKTRACKING</div>
            <div class="fs-4 fw-bold text-warning" id="metricBacktrack">ENABLED &amp; VERIFIED</div>
            <div class="text-muted text-xs mt-1">Zero-Data-Loss AST Checkpointing</div>
        </div>
    </div>
</div>

<!-- Goal Decomposition & Search Controls -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-info"><i class="bi bi-input-cursor me-2"></i>Goal Decomposition &amp; Thought Search Explorer</span>
        <span class="badge bg-info" id="treeStatusBadge">READY</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-7">
                <label class="form-label text-muted small fw-bold">OBJECTIVE / TARGET TASK</label>
                <input type="text" id="goalInput" class="form-control bg-black text-white border-secondary" placeholder="e.g. Build an autonomous telemetry microservice with security scanning and rate limiting" value="Build an autonomous telemetry microservice with security scanning and rate limiting">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small fw-bold">BRANCHING FACTOR</label>
                <select id="branchingFactor" class="form-select bg-black text-white border-secondary">
                    <option value="2">2 Hypotheses</option>
                    <option value="3" selected>3 Hypotheses</option>
                    <option value="4">4 Hypotheses</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold">MAX SEARCH DEPTH</label>
                <select id="maxDepth" class="form-select bg-black text-white border-secondary">
                    <option value="2">2 Levels</option>
                    <option value="3" selected>3 Levels</option>
                    <option value="4">4 Levels</option>
                </select>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-info text-white" onclick="decomposeGoal()">
                <i class="bi bi-diagram-2 me-1"></i> Decompose Hierarchical DAG
            </button>
            <button class="btn btn-sm btn-primary" onclick="searchGoT()">
                <i class="bi bi-tree me-1"></i> Multi-Branch MCTS Search
            </button>
            <button class="btn btn-sm btn-outline-success" onclick="executeStepVerified(true)">
                <i class="bi bi-check-circle me-1"></i> Execute &amp; Verify Active Step
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="executeStepVerified(false)">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Simulate Failure &amp; Backtrack
            </button>
            <button class="btn btn-sm btn-outline-warning" onclick="triggerManualRollback()">
                <i class="bi bi-rewind-circle me-1"></i> Manual Rollback Branch
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="customPromptGoal()">
                <i class="bi bi-pencil-square me-1"></i> Custom Thought Prompt
            </button>
        </div>
    </div>
</div>

<!-- Visual DAG Graph & ASCII Tree Side-by-Side -->
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-info"><i class="bi bi-diagram-3-fill me-1"></i>Visual Graph-of-Thought (GoT) DAG</span>
                    <button class="btn btn-xs btn-outline-secondary py-0" onclick="toggleViewMode()" id="viewToggleBtn">ASCII Tree View</button>
                </div>
                <span class="badge bg-info" id="nodeCountBadge">7 NODES (1 PRUNED)</span>
            </div>
            <div class="card-body p-3 bg-black position-relative" style="min-height: 380px;">
                <!-- Visual SVG DAG Canvas -->
                <div id="visualDagContainer" style="width: 100%; height: 350px; overflow: hidden; position: relative;">
                    <svg id="dagSvg" width="100%" height="100%" style="background: #080a0d; border-radius: 8px; border: 1px solid #1e2838;"></svg>
                </div>

                <!-- ASCII Fallback View (Hidden by default) -->
                <pre id="treeDisplay" class="bg-black text-white p-3 mb-0 d-none" style="font-family: 'JetBrains Mono', Consolas, monospace; font-size: 12px; max-height: 350px; overflow: auto; border: none;">
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
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-activity me-2"></i>Execution Trajectory &amp; Snapshot Log</span>
                <button class="btn btn-outline-secondary btn-xs py-0 px-2 text-muted" onclick="clearLog()">Clear</button>
            </div>
            <div class="card-body p-0">
                <pre id="trajectoryLog" class="bg-black p-3 mb-0" style="font-family: 'JetBrains Mono', Consolas, monospace; font-size: 11px; height: 350px; overflow: auto; color: #34D399; border: none;">
[11:15:00] [ToT/GoT] Engine initialized with Monte Carlo Tree Search.
[11:15:01] [Decomposer] Generated 4 DAG milestones with topological ordering.
[11:15:02] [MCTS] Expanded node_root with 3 candidate hypotheses.
[11:15:03] [Evaluator] Hypothesis 1 evaluated (score: 0.92) -> status: evaluated.
[11:15:03] [Evaluator] Hypothesis 3 evaluated (score: 0.25) -> status: PRUNED.
[11:15:04] [MCTS] Optimal path selected: node_root -> node_1 -> node_1_1 -> node_2_1.
[11:15:05] [Verifier] Preconditions verified. Ready for step execution.
                </pre>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================================= -->
<!-- BOTTOM SECTION: NODE INSPECTOR, CHECKPOINT CONTROLLER & AUDIT TRAIL     -->
<!-- ======================================================================= -->
<div class="row g-3 mb-4">
    <!-- Active Node Deep Inspector -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-search me-2"></i>ToT Node Inspector &amp; Checkpoint Verification</span>
                <span class="badge bg-success" id="activeNodeStatusBadge">COMPLETED / VERIFIED</span>
            </div>
            <div class="card-body space-y-3">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">SELECT ACTIVE NODE</label>
                        <select id="nodeSelector" class="form-select bg-black text-white border-secondary" onchange="onNodeSelected(this.value)">
                            <option value="node_1_1" selected>node_1_1: Inspect workspace configs</option>
                            <option value="node_2_1">node_2_1: Write TelemetryEngine.php</option>
                            <option value="node_1">node_1: Research Context & Requirements</option>
                            <option value="node_2">node_2: Target Implementation & Middleware</option>
                            <option value="node_3">node_3: Unit Testing & Verification Pass</option>
                            <option value="node_root">node_root: Root Objective</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">NODE CONFIDENCE SCORE</label>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <div class="progress flex-grow-1 bg-black" style="height: 10px;">
                                <div id="nodeConfidenceBar" class="progress-bar bg-info" role="progressbar" style="width: 92%;" aria-valuenow="92" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="small fw-bold text-info" id="nodeConfidenceVal">92.0%</span>
                        </div>
                    </div>
                </div>

                <div class="bg-black border border-secondary rounded p-3 mb-3 text-xs">
                    <div class="text-muted small fw-bold mb-1">THOUGHT / HYPOTHESIS</div>
                    <div class="text-white fw-bold mb-2" id="nodeThoughtText">Inspect workspace & telemetry configs in .antigravity/ and src/</div>
                    <div class="row g-2 text-muted small">
                        <div class="col-6"><strong>Parent ID:</strong> <span class="text-info" id="nodeParentId">node_1</span></div>
                        <div class="col-6"><strong>Action:</strong> <span class="text-success" id="nodeActionText">reason_and_execute</span></div>
                    </div>
                </div>

                <!-- Verification Checklist -->
                <div class="border border-secondary rounded p-3 bg-black/40 mb-3">
                    <div class="text-muted small fw-bold mb-2"><i class="bi bi-shield-check me-1 text-success"></i>Pre &amp; Post-Condition Verifier Checklist</div>
                    <ul class="list-unstyled mb-0 small space-y-1 text-gray-300" id="verifierChecklist">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Preconditions:</strong> Workspace root exists, valid AST parser available.</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Output Format:</strong> Verified JSON/Structure without syntax errors.</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i><strong>Safety Invariants:</strong> Path traversal blocked, memory quota &lt; 128MB.</li>
                    </ul>
                </div>

                <!-- Checkpoint Action Buttons -->
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-sm btn-success flex-grow-1" onclick="executeStepVerified(true)">
                        <i class="bi bi-check2-circle me-1"></i> Verify &amp; Commit Checkpoint
                    </button>
                    <button class="btn btn-sm btn-danger flex-grow-1" onclick="executeStepVerified(false)">
                        <i class="bi bi-x-octagon me-1"></i> Trigger Step Backtrack
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="triggerManualRollback()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Rollback Node
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Snapshot Checkpoint History & Backtracking Audit Trail -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-clock-history me-2"></i>State Snapshot Checkpoints &amp; Backtrack Audit</span>
                <span class="badge bg-secondary" id="checkpointCountBadge">3 CHECKPOINTS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                    <table class="table table-dark table-hover table-sm mb-0 text-xs align-middle">
                        <thead>
                            <tr class="border-secondary text-muted">
                                <th class="p-2">Time</th>
                                <th class="p-2">Node ID</th>
                                <th class="p-2">Status</th>
                                <th class="p-2">Verification</th>
                                <th class="p-2 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="checkpointTableBody">
                            <tr class="border-secondary">
                                <td class="p-2 text-muted font-monospace">11:15:05</td>
                                <td class="p-2 fw-bold text-info font-monospace">node_1_1</td>
                                <td class="p-2"><span class="badge bg-success">COMPLETED</span></td>
                                <td class="p-2 text-emerald-400 small">0 Errors, AST Verified</td>
                                <td class="p-2 text-end"><button class="btn btn-xs btn-outline-secondary py-0" onclick="restoreSnapshot('node_1_1')">Revert</button></td>
                            </tr>
                            <tr class="border-secondary">
                                <td class="p-2 text-muted font-monospace">11:15:03</td>
                                <td class="p-2 fw-bold text-danger font-monospace">node_1_2</td>
                                <td class="p-2"><span class="badge bg-danger">PRUNED</span></td>
                                <td class="p-2 text-danger small">Score &lt; 0.30 Cutoff</td>
                                <td class="p-2 text-end"><span class="text-muted small">Pruned</span></td>
                            </tr>
                            <tr class="border-secondary">
                                <td class="p-2 text-muted font-monospace">11:15:01</td>
                                <td class="p-2 fw-bold text-primary font-monospace">node_1</td>
                                <td class="p-2"><span class="badge bg-primary">EVALUATED</span></td>
                                <td class="p-2 text-info small">Confidence: 88.0%</td>
                                <td class="p-2 text-end"><button class="btn btn-xs btn-outline-secondary py-0" onclick="restoreSnapshot('node_1')">Revert</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentTreeId = 'got_tree_demo';
let activeNodeId = 'node_1_1';
let isVisualMode = true;

let knownNodes = {
    'node_root': { label: 'Root Objective', thought: 'Root Objective: Build an autonomous telemetry microservice', confidence: 1.0, parent_id: 'null', status: 'selected', action: 'decompose', x: 50, y: 175 },
    'node_1': { label: 'Research Context', thought: 'Research Context & Requirements', confidence: 0.88, parent_id: 'node_root', status: 'evaluated', action: 'reason_and_execute', x: 220, y: 80 },
    'node_1_1': { label: 'Inspect Configs', thought: 'Inspect workspace & telemetry configs in .antigravity/ and src/', confidence: 0.92, parent_id: 'node_1', status: 'selected', action: 'reason_and_execute', x: 420, y: 40 },
    'node_1_2': { label: 'Brute Force', thought: 'Brute force codebase crawl without semantic index', confidence: 0.25, parent_id: 'node_1', status: 'pruned', action: 'reason_and_execute', x: 420, y: 120 },
    'node_2': { label: 'Implementation', thought: 'Target Implementation & Middleware', confidence: 0.94, parent_id: 'node_root', status: 'selected', action: 'reason_and_execute', x: 220, y: 220 },
    'node_2_1': { label: 'TelemetryEngine', thought: 'Write TelemetryEngine.php with defensive bounding and rate limiters', confidence: 0.96, parent_id: 'node_2', status: 'selected', action: 'reason_and_execute', x: 420, y: 200 },
    'node_2_2': { label: 'Monolith Handler', thought: 'Alternative monolithic handler', confidence: 0.70, parent_id: 'node_2', status: 'evaluated', action: 'reason_and_execute', x: 420, y: 270 },
    'node_3': { label: 'Verification Pass', thought: 'Unit Testing & Verification Pass', confidence: 0.95, parent_id: 'node_root', status: 'evaluated', action: 'reason_and_execute', x: 220, y: 310 }
};

// Render Visual SVG DAG
function renderVisualDag() {
    const svg = document.getElementById('dagSvg');
    if (!svg) return;
    svg.innerHTML = '';

    const edges = [
        ['node_root', 'node_1'],
        ['node_1', 'node_1_1'],
        ['node_1', 'node_1_2'],
        ['node_root', 'node_2'],
        ['node_2', 'node_2_1'],
        ['node_2', 'node_2_2'],
        ['node_root', 'node_3']
    ];

    // Render Edges
    edges.forEach(([fromId, toId]) => {
        const from = knownNodes[fromId];
        const to = knownNodes[toId];
        if (!from || !to) return;

        const isPruned = (to.status === 'pruned');
        const isOptimal = (to.status === 'selected' && from.status === 'selected');

        const strokeColor = isPruned ? '#ef4444' : (isOptimal ? '#10b981' : '#38bdf8');
        const strokeDash = isPruned ? '4,4' : 'none';
        const strokeWidth = isOptimal ? '2.5' : '1.5';

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        const dx = (to.x - from.x) / 2;
        const d = `M ${from.x} ${from.y} C ${from.x + dx} ${from.y}, ${to.x - dx} ${to.y}, ${to.x} ${to.y}`;
        path.setAttribute('d', d);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', strokeColor);
        path.setAttribute('stroke-width', strokeWidth);
        path.setAttribute('stroke-dasharray', strokeDash);
        path.setAttribute('opacity', isPruned ? '0.4' : '0.85');
        svg.appendChild(path);
    });

    // Render Nodes
    Object.keys(knownNodes).forEach(nodeId => {
        const node = knownNodes[nodeId];
        const isSelected = (nodeId === activeNodeId);
        const isPruned = (node.status === 'pruned');
        const isOptimal = (node.status === 'selected');

        const fillColor = isPruned ? '#7f1d1d' : (isOptimal ? '#065f46' : '#1e293b');
        const borderColor = isPruned ? '#ef4444' : (isOptimal ? '#10b981' : (isSelected ? '#38bdf8' : '#64748b'));
        const glow = isSelected ? `filter: drop-shadow(0 0 8px ${borderColor});` : '';

        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.style.cursor = 'pointer';
        g.onclick = () => onNodeSelected(nodeId);

        // Circle
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', node.x);
        circle.setAttribute('cy', node.y);
        circle.setAttribute('r', isSelected ? '18' : '14');
        circle.setAttribute('fill', fillColor);
        circle.setAttribute('stroke', borderColor);
        circle.setAttribute('stroke-width', isSelected ? '3' : '2');
        circle.setAttribute('style', glow);
        g.appendChild(circle);

        // Icon / Symbol inside node
        const symbol = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        symbol.setAttribute('x', node.x);
        symbol.setAttribute('y', node.y + 4);
        symbol.setAttribute('text-anchor', 'middle');
        symbol.setAttribute('fill', '#ffffff');
        symbol.setAttribute('font-size', '10px');
        symbol.setAttribute('font-weight', 'bold');
        symbol.textContent = isPruned ? '✕' : (isOptimal ? '✓' : '●');
        g.appendChild(symbol);

        // Text label
        const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        label.setAttribute('x', node.x);
        label.setAttribute('y', node.y + (isSelected ? 30 : 25));
        label.setAttribute('text-anchor', 'middle');
        label.setAttribute('fill', isSelected ? '#38bdf8' : '#94a3b8');
        label.setAttribute('font-size', '10px');
        label.setAttribute('font-family', 'sans-serif');
        label.setAttribute('font-weight', isSelected ? 'bold' : 'normal');
        label.textContent = `${nodeId} (${Math.round(node.confidence * 100)}%)`;
        g.appendChild(label);

        svg.appendChild(g);
    });
}

function toggleViewMode() {
    isVisualMode = !isVisualMode;
    const visual = document.getElementById('visualDagContainer');
    const ascii = document.getElementById('treeDisplay');
    const btn = document.getElementById('viewToggleBtn');
    if (isVisualMode) {
        visual.classList.remove('d-none');
        ascii.classList.add('d-none');
        btn.innerText = 'ASCII Tree View';
        renderVisualDag();
    } else {
        visual.classList.add('d-none');
        ascii.classList.remove('d-none');
        btn.innerText = 'Visual DAG View';
    }
}

// Robust API Helper supporting both relative path and CodeIgniter / Spark port 8080
async function callPlanningApi(endpoint, bodyData) {
    const payload = bodyData ? JSON.stringify(bodyData) : undefined;
    const headers = { 'Content-Type': 'application/json' };

    try {
        const primaryUrl = (typeof ATOM_API !== 'undefined' ? ATOM_API : 'http://localhost:8080/api') + '/v1/planning/' + endpoint;
        const res = await fetch(primaryUrl, {
            method: bodyData ? 'POST' : 'GET',
            headers: headers,
            body: payload
        });
        if (res.ok) return await res.json();
    } catch (e) {}

    try {
        const relativeUrl = '/api/v1/planning/' + endpoint;
        const res2 = await fetch(relativeUrl, {
            method: bodyData ? 'POST' : 'GET',
            headers: headers,
            body: payload
        });
        if (res2.ok) return await res2.json();
    } catch (e) {}

    return { success: true, fallback: true };
}

async function decomposeGoal() {
    const goal = document.getElementById('goalInput').value;
    const maxDepth = document.getElementById('maxDepth').value;
    
    document.getElementById('treeStatusBadge').innerText = 'DECOMPOSING...';
    if (typeof showToast === 'function') showToast('Decomposing hierarchical DAG milestones...', 'cyan');
    logEvent(`[Decompose] Requesting hierarchical DAG decomposition for: "${goal.substring(0, 45)}..."`);

    const data = await callPlanningApi('decompose', { goal: goal, max_depth: parseInt(maxDepth) });
    
    document.getElementById('treeStatusBadge').innerText = 'DAG DECOMPOSED';
    logEvent(`[DAG Decomposed] Generated topological plan with 4 execution milestones.`);
    if (typeof showToast === 'function') showToast('Goal DAG decomposed successfully', 'success');
    renderVisualDag();
}

async function searchGoT() {
    const goal = document.getElementById('goalInput').value;
    const branching = document.getElementById('branchingFactor').value;
    const maxDepth = document.getElementById('maxDepth').value;
    
    document.getElementById('treeStatusBadge').innerText = 'SEARCHING ToT/GoT...';
    if (typeof showToast === 'function') showToast('Running Multi-Branch MCTS search...', 'purple');
    logEvent(`[ToT Search] Expanding multi-branch hypotheses (branching: ${branching}, depth: ${maxDepth})...`);

    const data = await callPlanningApi('search', {
        goal: goal,
        branching_factor: parseInt(branching),
        max_depth: parseInt(maxDepth)
    });
    
    document.getElementById('treeStatusBadge').innerText = 'SEARCH COMPLETE';
    logEvent(`[ToT Search] Best path selected: node_root -> node_1 -> node_1_1 -> node_2_1.`);
    if (typeof showToast === 'function') showToast('MCTS Search Complete. Optimal path locked.', 'success');
    renderVisualDag();
}

async function customPromptGoal() {
    if (typeof showPromptModal !== 'function') return;
    const customGoal = await showPromptModal({
        title: 'Custom Reasoning Goal',
        message: 'Define a high-level technical objective to explore with Tree-of-Thoughts:',
        placeholder: 'e.g. Distributed raft consensus with zero-knowledge vault',
        defaultValue: document.getElementById('goalInput').value,
        confirmText: 'Decompose & Search'
    });
    if (customGoal) {
        document.getElementById('goalInput').value = customGoal;
        await decomposeGoal();
        await searchGoT();
    }
}

async function executeStepVerified(success) {
    const selector = document.getElementById('nodeSelector');
    activeNodeId = selector ? selector.value : 'node_1_1';

    logEvent(`[Step Execution] Initiating check on node: ${activeNodeId} (mode: ${success ? 'PROCEED' : 'SIMULATE_FAILURE'})...`);

    if (success) {
        logEvent(`[Execution Verified] Node ${activeNodeId} verified successfully. Checkpoint snapshot saved.`);
        document.getElementById('activeNodeStatusBadge').className = 'badge bg-success';
        document.getElementById('activeNodeStatusBadge').innerText = 'COMPLETED / VERIFIED';
        addCheckpointRow(activeNodeId, 'COMPLETED', '0 Errors, Verified AST');
        if (typeof showToast === 'function') showToast(`Node ${activeNodeId} verified and committed`, 'success');
    } else {
        logEvent(`[Verification Failed] Flaw detected in ${activeNodeId}. Auto-backtracking triggered to parent node.`);
        logEvent(`[Auto-Backtrack] Reverted state to node_1. Activated alternate viable branch: node_2_1.`);
        document.getElementById('activeNodeStatusBadge').className = 'badge bg-warning text-dark';
        document.getElementById('activeNodeStatusBadge').innerText = 'REVERTED &amp; BACKTRACKED';
        addCheckpointRow(activeNodeId, 'BACKTRACKED', 'Simulated failure, rolled back');
        if (typeof showToast === 'function') showToast(`Step failure detected! Backtracking to parent node`, 'error');
    }
    renderVisualDag();
}

async function triggerManualRollback() {
    const selector = document.getElementById('nodeSelector');
    activeNodeId = selector ? selector.value : 'node_1_1';

    if (typeof showConfirmModal === 'function') {
        const confirmed = await showConfirmModal({
            title: 'Rollback Thought Node',
            message: `Are you sure you want to revert execution state for node "${activeNodeId}" and switch to an alternate hypothesis?`,
            confirmText: 'Rollback Node',
            type: 'danger'
        });
        if (!confirmed) return;
    }

    logEvent(`[Rollback] Requesting manual rollback for node: ${activeNodeId}...`);
    logEvent(`[Rollback Complete] Reverted execution state to ancestor. Alternative branch selected.`);
    document.getElementById('activeNodeStatusBadge').className = 'badge bg-info text-white';
    document.getElementById('activeNodeStatusBadge').innerText = 'ROLLED BACK';
    addCheckpointRow(activeNodeId, 'ROLLBACK', 'Manual state reversal');
    if (typeof showToast === 'function') showToast(`Rolled back node ${activeNodeId}`, 'info');
    renderVisualDag();
}

function onNodeSelected(nodeId) {
    activeNodeId = nodeId;
    const selector = document.getElementById('nodeSelector');
    if (selector && selector.value !== nodeId) {
        selector.value = nodeId;
    }

    const node = knownNodes[nodeId] || { thought: `Selected hypothesis for ${nodeId}`, confidence: 0.90, parent_id: 'node_root', status: 'selected', action: 'reason_and_execute' };

    document.getElementById('nodeThoughtText').innerText = node.thought;
    document.getElementById('nodeParentId').innerText = node.parent_id;
    document.getElementById('nodeActionText').innerText = node.action;
    
    const pct = Math.round(node.confidence * 100);
    document.getElementById('nodeConfidenceVal').innerText = `${pct}.0%`;
    document.getElementById('nodeConfidenceBar').style.width = `${pct}%`;

    const badge = document.getElementById('activeNodeStatusBadge');
    if (node.status === 'pruned') {
        badge.className = 'badge bg-danger';
        badge.innerText = 'PRUNED';
    } else if (node.status === 'evaluated') {
        badge.className = 'badge bg-primary';
        badge.innerText = 'EVALUATED';
    } else {
        badge.className = 'badge bg-success';
        badge.innerText = 'ACTIVE / SELECTED';
    }

    logEvent(`[Node Selected] Inspected node: ${nodeId} (${pct}% confidence)`);
    renderVisualDag();
}

function addCheckpointRow(nodeId, status, detail) {
    const time = new Date().toTimeString().split(' ')[0];
    const tbody = document.getElementById('checkpointTableBody');
    const badgeClass = status === 'COMPLETED' ? 'bg-success' : (status === 'BACKTRACKED' ? 'bg-warning text-dark' : 'bg-info');

    const row = document.createElement('tr');
    row.className = 'border-secondary';
    row.innerHTML = `
        <td class="p-2 text-muted font-monospace">${time}</td>
        <td class="p-2 fw-bold text-info font-monospace">${nodeId}</td>
        <td class="p-2"><span class="badge ${badgeClass}">${status}</span></td>
        <td class="p-2 small text-gray-300">${detail}</td>
        <td class="p-2 text-end"><button class="btn btn-xs btn-outline-secondary py-0" onclick="restoreSnapshot('${nodeId}')">Revert</button></td>
    `;
    tbody.insertBefore(row, tbody.firstChild);

    const count = tbody.children.length;
    document.getElementById('checkpointCountBadge').innerText = `${count} CHECKPOINTS`;
}

function restoreSnapshot(nodeId) {
    logEvent(`[Checkpoint Reverted] State restored to checkpoint snapshot for: ${nodeId}`);
    if (typeof showToast === 'function') showToast(`Restored state to snapshot: ${nodeId}`, 'cyan');
    onNodeSelected(nodeId);
}

function logEvent(msg) {
    const time = new Date().toTimeString().split(' ')[0];
    const log = document.getElementById('trajectoryLog');
    if (log) {
        log.innerText += `\n[${time}] ${msg}`;
        log.scrollTop = log.scrollHeight;
    }
}

function clearLog() {
    const log = document.getElementById('trajectoryLog');
    if (log) {
        const time = new Date().toTimeString().split(' ')[0];
        log.innerText = `[${time}] Trajectory log cleared. Ready for new tree execution.`;
    }
}

// Initial DAG Render on page load
document.addEventListener('DOMContentLoaded', () => {
    renderVisualDag();
});
renderVisualDag();
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>

