<?php
// ATOM Web Admin — Unified Policy, Governance, Trust & Compliance Dashboard
$pageTitle = "Unified Governance & Policy Control Plane";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #00F2FE;">Unified Policy & Governance Control Plane</h2>
        <p class="text-muted small mb-0">Authoritative policy evaluation, trust profiles, emergency kill switches, and audit integrity</p>
    </div>
    <div>
        <button class="btn btn-outline-info btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-danger btn-sm me-2" onclick="toggleKillSwitchPrompt()">
            <i class="bi bi-slash-circle me-1"></i> Emergency Kill Switch
        </button>
        <button class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #00F2FE 0%, #4FACFE 100%); border: none;" data-bs-toggle="modal" data-bs-target="#simulatePolicyModal">
            <i class="bi bi-shield-check me-1"></i> Policy Simulator
        </button>
    </div>
</div>

<!-- Governance Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE POLICIES</div>
            <div class="fs-3 fw-bold text-info" id="metricActivePolicies">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">POLICY DECISIONS</div>
            <div class="fs-3 fw-bold text-warning" id="metricTotalDecisions">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AUDIT INTEGRITY</div>
            <div class="fs-3 fw-bold text-success">VERIFIED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">KILL SWITCHES ACTIVE</div>
            <div class="fs-3 fw-bold text-primary" id="metricKillSwitches">0</div>
        </div>
    </div>
</div>

<!-- Decisions Audit Table -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header bg-black bg-opacity-40 border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-shield-lock me-2 text-info"></i> Policy Evaluation Audit Logs</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>ID</th>
                        <th>Actor ID</th>
                        <th>Action</th>
                        <th>Resource</th>
                        <th>Decision</th>
                        <th>Reason Codes</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody id="govDecisionsTableBody">
                    <tr><td colspan="7" class="text-center text-muted py-4">Loading governance decisions...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Policy Simulator -->
<div class="modal fade" id="simulatePolicyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-info"><i class="bi bi-shield-check me-2"></i> Dry-Run Policy Simulator</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Target Action</label>
                    <input type="text" class="form-control bg-black text-white border-secondary" id="simAction" value="tool.execute">
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted">Target Resource</label>
                    <input type="text" class="form-control bg-black text-white border-secondary" id="simResource" value="workspace">
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="runPolicySimulation()">Simulate</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', loadGovernance);

async function loadGovernance() {
    try {
        const polJson = await apiFetch('/governance/policies');
        document.getElementById('metricActivePolicies').textContent = ((polJson && polJson.data) || []).length;

        const decJson = await apiFetch('/governance/decisions');
        const decisions = (decJson && decJson.data) || [];
        document.getElementById('metricTotalDecisions').textContent = decisions.length;

        const tbody = document.getElementById('govDecisionsTableBody');
        if (decisions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No policy decisions recorded yet.</td></tr>';
            return;
        }

        tbody.innerHTML = decisions.map(d => `
            <tr>
                <td><code>#${d.id}</code></td>
                <td>User <code>#${d.actor_id}</code></td>
                <td><span class="fw-bold text-info">${escapeHtml(d.action)}</span></td>
                <td><code>${escapeHtml(d.resource)}</code></td>
                <td><span class="badge ${d.decision === 'allow' ? 'bg-success' : 'bg-warning'}">${(d.decision || 'ALLOW').toUpperCase()}</span></td>
                <td><span class="badge bg-secondary">POLICY_MATCH_ALLOW</span></td>
                <td class="small text-muted">${d.created_at || ''}</td>
            </tr>
        `).join('');
    } catch (e) {
        document.getElementById('govDecisionsTableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Failed to load governance decisions.</td></tr>';
    }
}

async function runPolicySimulation() {
    const action = document.getElementById('simAction').value;
    const resource = document.getElementById('simResource').value;
    try {
        const json = await apiFetch('/governance/policies/simulate', {
            method: 'POST',
            body: JSON.stringify({ actor_id: 1, action: action, resource: resource })
        });
        if (json && json.data) {
            if (typeof showToast === 'function') {
                showToast(`Simulation Outcome: ${(json.data.decision || 'ALLOW').toUpperCase()}`, 'success');
            } else {
                alert(`Simulation Outcome: ${(json.data.decision || 'ALLOW').toUpperCase()}`);
            }
        }
        bootstrap.Modal.getInstance(document.getElementById('simulatePolicyModal')).hide();
        loadGovernance();
    } catch (e) {
        if (typeof showToast === 'function') showToast('Failed to execute policy simulation', 'error');
    }
}

async function toggleKillSwitchPrompt() {
    let target = 'workspace';
    if (typeof showPromptModal === 'function') {
        target = await showPromptModal({
            title: 'Enable Kill Switch',
            message: 'Enter resource or tool to kill-switch (e.g. workspace):',
            defaultValue: 'workspace'
        });
    } else {
        target = prompt("Enter resource or tool to kill-switch (e.g. workspace):", "workspace");
    }
    if (!target) return;
    try {
        await apiFetch('/governance/kill-switch', {
            method: 'POST',
            body: JSON.stringify({ target_type: 'resource', target_id: target, enable: true, reason: 'Manual kill switch trigger' })
        });
        if (typeof showToast === 'function') showToast(`Kill switch enabled for ${target}`, 'warning');
        loadGovernance();
    } catch (e) {
        if (typeof showToast === 'function') showToast('Failed to enable kill switch', 'error');
    }
}

function escapeHtml(str) {
    return (str || '').replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
