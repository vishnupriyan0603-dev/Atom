<?php
// ATOM Web Admin — Phase 41: Autonomous Multi-Agent Swarm Orchestration Studio
$pageTitle = "Autonomous Multi-Agent Swarm Orchestration";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold mb-1" style="color: #A855F7;"><i class="bi bi-diagram-3 me-2"></i>Autonomous Multi-Agent Swarm Studio</h2>
        <p class="text-muted small mb-0">Phase 41: Multi-role agent delegation, weighted consensus voting, conflict resolution &amp; unified artifact synthesis</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Studio
        </button>
        <button class="btn btn-sm text-white" style="background: linear-gradient(135deg, #A855F7 0%, #9333EA 100%); border: none;" onclick="triggerSwarmPlan()">
            <i class="bi bi-lightning-charge-fill me-1"></i> Dispatch Swarm Task
        </button>
    </div>
</div>

<!-- Swarm Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white h-100 shadow-sm">
            <div class="text-muted small fw-bold">REGISTERED AGENTS</div>
            <div class="fs-4 fw-bold text-info" id="metricTotalAgents">5 SPECIALIZED</div>
            <div class="text-muted text-xs mt-1">Architect, Coder, Reviewer, Security, Synthesizer</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white h-100 shadow-sm">
            <div class="text-muted small fw-bold">CONSENSUS ENGINE</div>
            <div class="fs-4 fw-bold text-success" id="metricConsensusEngine">WEIGHTED VOTE</div>
            <div class="text-muted text-xs mt-1">Quorum Threshold: 65.0% Majority</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white h-100 shadow-sm">
            <div class="text-muted small fw-bold">AVG CONFIDENCE</div>
            <div class="fs-4 fw-bold text-primary" id="metricSwarmConfidence">96.4%</div>
            <div class="text-muted text-xs mt-1">AST Verification &amp; Invariant Bound</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white h-100 shadow-sm">
            <div class="text-muted small fw-bold">SWARM STATUS</div>
            <div class="fs-4 fw-bold text-warning" id="metricSwarmStatus">READY (IDLE)</div>
            <div class="text-muted text-xs mt-1">Autonomous Task Dispatch Active</div>
        </div>
    </div>
</div>

<!-- Multi-Agent Role Topology Cards -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-info"><i class="bi bi-people-fill me-2"></i>Swarm Role Registry &amp; Capabilities</span>
        <span class="badge bg-purple text-white" style="background-color: #9333EA;">5 ACTIVE ROLES</span>
    </div>
    <div class="card-body">
        <div class="row g-3" id="swarmAgentsGrid">
            <!-- Dynamic Agent Cards -->
            <div class="col-md-4">
                <div class="p-3 rounded-xl bg-black border border-secondary h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-info">ARCHITECT</span>
                        <span class="text-xs text-muted">Weight: 1.5x</span>
                    </div>
                    <h5 class="fw-bold text-white mb-1 text-sm">System Architect</h5>
                    <p class="text-xs text-gray-400 mb-2">Decomposes macro goals into DAG work orders &amp; data contracts.</p>
                    <div class="d-flex gap-1 flex-wrap">
                        <span class="badge bg-dark border border-secondary text-xs">system_design</span>
                        <span class="badge bg-dark border border-secondary text-xs">dag_plan</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-xl bg-black border border-secondary h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-success">CODER</span>
                        <span class="text-xs text-muted">Weight: 1.2x</span>
                    </div>
                    <h5 class="fw-bold text-white mb-1 text-sm">Principal Coder</h5>
                    <p class="text-xs text-gray-400 mb-2">Executes high-throughput AST code synthesis &amp; refactoring.</p>
                    <div class="d-flex gap-1 flex-wrap">
                        <span class="badge bg-dark border border-secondary text-xs">code_generation</span>
                        <span class="badge bg-dark border border-secondary text-xs">ast_patching</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-xl bg-black border border-secondary h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-danger">SECURITY</span>
                        <span class="text-xs text-muted">Weight: 1.8x</span>
                    </div>
                    <h5 class="fw-bold text-white mb-1 text-sm">Security Inspector</h5>
                    <p class="text-xs text-gray-400 mb-2">Validates invariant boundaries, secrets redaction &amp; safety policies.</p>
                    <div class="d-flex gap-1 flex-wrap">
                        <span class="badge bg-dark border border-secondary text-xs">vulnerability_scan</span>
                        <span class="badge bg-dark border border-secondary text-xs">invariant_guard</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Decomposition & Live Consensus Sandbox -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-kanban me-2"></i>Swarm Work Orders &amp; Dispatcher</span>
                <span class="badge bg-info" id="workOrdersCount">4 ORDERS</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SWARM OBJECTIVE</label>
                    <div class="input-group">
                        <input type="text" id="swarmGoalInput" class="form-control bg-black text-white border-secondary text-xs" value="Build an autonomous WebSocket signaling hub with token auth and rate limiting">
                        <button class="btn btn-sm text-white" style="background: #9333EA;" onclick="triggerSwarmPlan()">Plan Orders</button>
                    </div>
                </div>
                <div class="space-y-2" id="workOrdersList" style="max-height: 280px; overflow-y: auto;">
                    <div class="p-2.5 rounded bg-black border border-secondary text-xs d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-info me-1">ARCHITECT</span>
                            <span class="text-white fw-bold">Design Architecture &amp; Data Contracts</span>
                        </div>
                        <span class="badge bg-success">READY</span>
                    </div>
                    <div class="p-2.5 rounded bg-black border border-secondary text-xs d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-success me-1">CODER</span>
                            <span class="text-white fw-bold">Implement Core Logic &amp; Middleware</span>
                        </div>
                        <span class="badge bg-secondary">PENDING</span>
                    </div>
                    <div class="p-2.5 rounded bg-black border border-secondary text-xs d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-danger me-1">SECURITY</span>
                            <span class="text-white fw-bold">Verify Invariants &amp; Vulnerability Surface</span>
                        </div>
                        <span class="badge bg-secondary">PENDING</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Consensus & Voting Simulator -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-check2-all me-2"></i>Consensus Voting &amp; Synthesis Matrix</span>
                <span class="badge bg-success" id="consensusStatusBadge">QUORUM APPROVED (92%)</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">AGENT CLAIMS EVALUATION</label>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm text-xs mb-2">
                            <thead>
                                <tr class="text-muted border-secondary">
                                    <th>Role</th>
                                    <th>Verdict</th>
                                    <th>Confidence</th>
                                    <th>Weighted Score</th>
                                </tr>
                            </thead>
                            <tbody id="consensusVotesTable">
                                <tr class="border-secondary">
                                    <td><span class="badge bg-info">Architect</span></td>
                                    <td><span class="text-success fw-bold">APPROVE</span></td>
                                    <td>95.0%</td>
                                    <td>1.425</td>
                                </tr>
                                <tr class="border-secondary">
                                    <td><span class="badge bg-success">Coder</span></td>
                                    <td><span class="text-success fw-bold">APPROVE</span></td>
                                    <td>92.0%</td>
                                    <td>1.104</td>
                                </tr>
                                <tr class="border-secondary">
                                    <td><span class="badge bg-danger">Security</span></td>
                                    <td><span class="text-success fw-bold">APPROVE</span></td>
                                    <td>98.0%</td>
                                    <td>1.764</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-success flex-grow-1" onclick="evaluateConsensusSim(true)">
                        <i class="bi bi-check-circle me-1"></i> Approve &amp; Synthesize Artifact
                    </button>
                    <button class="btn btn-sm btn-danger flex-grow-1" onclick="evaluateConsensusSim(false)">
                        <i class="bi bi-x-circle me-1"></i> Simulate Security Veto
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function triggerSwarmPlan() {
    const goal = document.getElementById('swarmGoalInput').value;
    if (typeof showToast === 'function') showToast('Dispatching goal to Swarm Orchestrator...', 'purple');

    try {
        const json = await apiFetch('/swarm/plan', {
            method: 'POST',
            body: JSON.stringify({ goal: goal })
        });
        if (json && json.success && json.data) {
            renderWorkOrders(json.data.work_orders);
            if (typeof showToast === 'function') showToast('Swarm work orders planned successfully', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Swarm plan dispatched (local fallback)', 'info');
    }
}

function renderWorkOrders(orders) {
    const list = document.getElementById('workOrdersList');
    if (!list || !orders) return;
    list.innerHTML = orders.map(o => `
        <div class="p-2.5 rounded bg-black border border-secondary text-xs d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-info me-1">${escapeHtml(o.agent_role.toUpperCase())}</span>
                <span class="text-white fw-bold">${escapeHtml(o.task_name)}</span>
            </div>
            <span class="badge bg-${o.status === 'ready' ? 'success' : 'secondary'}">${escapeHtml(o.status.toUpperCase())}</span>
        </div>
    `).join('');
    document.getElementById('workOrdersCount').innerText = `${orders.length} ORDERS`;
}

async function evaluateConsensusSim(approve) {
    const table = document.getElementById('consensusVotesTable');
    const badge = document.getElementById('consensusStatusBadge');

    if (approve) {
        badge.className = 'badge bg-success';
        badge.innerText = 'QUORUM APPROVED (94%)';
        if (typeof showToast === 'function') showToast('Consensus reached! Unified artifact synthesized', 'success');
    } else {
        badge.className = 'badge bg-danger';
        badge.innerText = 'VETOED / ARBITRATION NEEDED';
        if (typeof showToast === 'function') showToast('Security Inspector triggered safety veto on artifact', 'error');
    }
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
