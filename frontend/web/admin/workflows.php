<?php
// ATOM Web Admin — Autonomous Workflow Engine Dashboard
$pageTitle = "Autonomous Workflow Engine";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #00F2FE;">Autonomous Workflow Engine</h2>
        <p class="text-muted small mb-0">Persistent, versioned, resumable, directed graph workflow orchestration</p>
    </div>
    <div>
        <button class="btn btn-outline-info btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #00F2FE 0%, #4FACFE 100%); border: none;" data-bs-toggle="modal" data-bs-target="#newWorkflowModal">
            <i class="bi bi-plus-circle me-1"></i> Create New Workflow
        </button>
    </div>
</div>

<!-- Workflow Performance Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL WORKFLOWS</div>
            <div class="fs-3 fw-bold text-info" id="metricTotalWorkflows">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL EXECUTIONS</div>
            <div class="fs-3 fw-bold text-warning" id="metricTotalExecutions">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMPLETED</div>
            <div class="fs-3 fw-bold text-success" id="metricCompletedExecutions">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE / RUNNING</div>
            <div class="fs-3 fw-bold text-primary" id="metricRunningExecutions">0</div>
        </div>
    </div>
</div>

<!-- Published Workflows Table -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header bg-black bg-opacity-40 border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-diagram-3 me-2 text-info"></i> Published Workflows</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>ID</th>
                        <th>Workflow Name</th>
                        <th>Status</th>
                        <th>Version</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="workflowsTableBody">
                    <tr><td colspan="6" class="text-center text-muted py-4">Loading workflows...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Create New Workflow -->
<div class="modal fade" id="newWorkflowModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-info"><i class="bi bi-diagram-3 me-2"></i> Create Autonomous Workflow</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Workflow Name</label>
                    <input type="text" class="form-control bg-black text-white border-secondary" id="workflowNameInput" placeholder="e.g. Daily Research & Code Review">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Description</label>
                    <textarea class="form-control bg-black text-white border-secondary" id="workflowDescInput" rows="2" placeholder="Description of the autonomous workflow graph..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="submitNewWorkflow()">Publish Workflow</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadWorkflows);

async function loadWorkflows() {
    try {
        const json = await apiFetch('/workflows');
        const workflows = (json && json.data) || [];

        document.getElementById('metricTotalWorkflows').textContent = workflows.length;

        const execJson = await apiFetch('/workflows/executions');
        const executions = (execJson && execJson.data) || [];

        document.getElementById('metricTotalExecutions').textContent = executions.length;
        document.getElementById('metricCompletedExecutions').textContent = executions.filter(e => e.status === 'completed').length;
        document.getElementById('metricRunningExecutions').textContent = executions.filter(e => e.status === 'running').length;

        const tbody = document.getElementById('workflowsTableBody');
        if (workflows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No workflows created yet.</td></tr>';
            return;
        }

        tbody.innerHTML = workflows.map(w => `
            <tr>
                <td><code>#${w.id}</code></td>
                <td><span class="fw-bold">${escapeHtml(w.name)}</span><br><small class="text-muted">${escapeHtml(w.description || '')}</small></td>
                <td><span class="badge bg-success">PUBLISHED</span></td>
                <td>v${w.current_version || 1}</td>
                <td class="small text-muted">${w.created_at || ''}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-info" onclick="triggerWorkflow(${w.id})">Execute</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        document.getElementById('workflowsTableBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load workflows.</td></tr>';
    }
}

async function submitNewWorkflow() {
    const name = document.getElementById('workflowNameInput').value.trim();
    const description = document.getElementById('workflowDescInput').value.trim();
    if (!name) return;

    try {
        await apiFetch('/workflows', {
            method: 'POST',
            body: JSON.stringify({ name, description })
        });
        bootstrap.Modal.getInstance(document.getElementById('newWorkflowModal')).hide();
        loadWorkflows();
    } catch (e) {
        if (typeof showToast === 'function') showToast('Failed to create workflow', 'error');
    }
}

async function triggerWorkflow(id) {
    try {
        await apiFetch(`/workflows/${id}/execute`, {
            method: 'POST',
            body: JSON.stringify({ input: { objective: 'Run automated research workflow' } })
        });
        if (typeof showToast === 'function') showToast(`Workflow #${id} execution dispatched successfully!`, 'success');
        loadWorkflows();
    } catch (e) {
        alert('Failed to execute workflow');
    }
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
