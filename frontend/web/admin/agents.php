<?php
// ATOM Web Admin — Controlled Agent Orchestration Engine Dashboard
$pageTitle = "Agent Orchestration Engine";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #00F2FE;">Controlled Agent & Swarm Orchestration Engine</h2>
        <p class="text-muted small mb-0">Monitor multi-step tasks, specialized agent swarms, risk gates, and execution telemetry</p>
    </div>
    <div>
        <button class="btn btn-outline-info btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #00F2FE 0%, #4FACFE 100%); border: none;" data-bs-toggle="modal" data-bs-target="#newTaskModal">
            <i class="bi bi-plus-circle me-1"></i> Launch New Agent Task / Swarm
        </button>
    </div>
</div>

<!-- Key Performance Metrics -->
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL AGENT TASKS</div>
            <div class="fs-3 fw-bold text-info" id="metricTotalTasks">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">RUNNING / PLANNED</div>
            <div class="fs-3 fw-bold text-warning" id="metricRunningTasks">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMPLETED</div>
            <div class="fs-3 fw-bold text-success" id="metricCompletedTasks">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">WAITING APPROVAL</div>
            <div class="fs-3 fw-bold text-danger" id="metricApprovalTasks">0</div>
        </div>
    </div>
</div>

<!-- Active Agent Tasks Table -->
<div class="card bg-dark border-secondary text-white">
    <div class="card-header bg-black bg-opacity-40 border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-cpu me-2 text-info"></i> Agent Tasks & Execution Logs</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>ID</th>
                        <th>Objective</th>
                        <th>Status</th>
                        <th>Current Step</th>
                        <th>Max Steps</th>
                        <th>Risk Level</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="agentTasksTableBody">
                    <tr><td colspan="8" class="text-center text-muted py-4">Loading agent tasks...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Launch New Task -->
<div class="modal fade" id="newTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-info"><i class="bi bi-robot me-2"></i> Launch Bounded Agent Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Task Objective</label>
                    <textarea class="form-control bg-black text-white border-secondary" id="taskObjectiveInput" rows="3" placeholder="Describe multi-step objective (e.g. Research CodeIgniter 4 migration guide, generate code patch, and verify output)..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="submitNewTask()">Launch Task</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadAgentTasks);

async function loadAgentTasks() {
    try {
        const res = await fetch('/api/v1/agents/tasks');
        const json = await res.json();
        const tasks = json.data || [];

        document.getElementById('metricTotalTasks').textContent = tasks.length;
        document.getElementById('metricRunningTasks').textContent = tasks.filter(t => t.status === 'running' || t.status === 'planning').length;
        document.getElementById('metricCompletedTasks').textContent = tasks.filter(t => t.status === 'completed').length;
        document.getElementById('metricApprovalTasks').textContent = tasks.filter(t => t.status === 'waiting_approval').length;

        const tbody = document.getElementById('agentTasksTableBody');
        if (tasks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No agent tasks recorded yet.</td></tr>';
            return;
        }

        tbody.innerHTML = tasks.map(t => `
            <tr>
                <td><code>#${t.id}</code></td>
                <td><span class="fw-bold">${escapeHtml(t.objective)}</span></td>
                <td><span class="badge ${getStatusBadge(t.status)}">${t.status.toUpperCase()}</span></td>
                <td>${t.current_step}</td>
                <td>${t.max_steps}</td>
                <td><span class="badge bg-secondary text-uppercase">${t.risk_level || 'low'}</span></td>
                <td class="small text-muted">${t.created_at || ''}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-info" onclick="viewTaskSteps(${t.id})">Steps</button>
                    ${t.status === 'running' ? `<button class="btn btn-sm btn-outline-danger" onclick="cancelTask(${t.id})">Cancel</button>` : ''}
                </td>
            </tr>
        `).join('');
    } catch (e) {
        document.getElementById('agentTasksTableBody').innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">Failed to load agent tasks.</td></tr>';
    }
}

async function submitNewTask() {
    const objective = document.getElementById('taskObjectiveInput').value.trim();
    if (!objective) return;

    try {
        await fetch('/api/v1/agents/tasks', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ objective })
        });
        bootstrap.Modal.getInstance(document.getElementById('newTaskModal')).hide();
        loadAgentTasks();
    } catch (e) {
        alert('Failed to launch agent task');
    }
}

function getStatusBadge(status) {
    switch (status) {
        case 'completed': return 'bg-success';
        case 'running': return 'bg-info text-dark';
        case 'planning': return 'bg-primary';
        case 'waiting_approval': return 'bg-warning text-dark';
        case 'failed': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
