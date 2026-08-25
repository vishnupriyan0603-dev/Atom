<?php
// ATOM Web Admin — Phase 48: Dynamic Attribute-Based Access Control (ABAC) & Zero-Trust Studio
$pageTitle = "ABAC Policy Engine & Zero-Trust Firewall (Phase 48)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #3B82F6;">Dynamic ABAC Policy Engine &amp; Zero-Trust Firewall</h2>
        <p class="text-muted small mb-0">Phase 48: Fine-Grained Attribute-Based Access Control (Subject, Resource, Action, Environment Context), Deny-Overrides Zero-Trust Algorithm &amp; Live Multi-Scenario Decision Simulation</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-primary fw-bold" onclick="runAbacBatchSimulation()">
            <i class="bi bi-shield-check me-1"></i> Run 3-Scenario Simulation
        </button>
    </div>
</div>

<!-- ABAC Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE POLICIES</div>
            <div class="fs-4 fw-bold text-info" id="metricPolicyCount">3 POLICIES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMBINING ALGORITHM</div>
            <div class="fs-4 fw-bold text-warning">DenyOverrides (Zero-Trust)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DEFAULT POSTURE</div>
            <div class="fs-4 fw-bold text-danger">EXPLICIT ALLOW REQUIRED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CONTEXT SENSORS</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">IP CIDR • MFA • Trust Score</div>
        </div>
    </div>
</div>

<!-- Main Section: Live Decision Simulator & Policy List -->
<div class="row g-4 mb-4">
    
    <!-- 1. Live Decision Evaluation Sandbox -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-cpu me-2"></i>Access Request Attribute Sandbox</span>
                <span class="badge bg-secondary">ABAC CONTEXT</span>
            </div>
            <div class="card-body">
                
                <!-- Subject Attributes -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">1. SUBJECT ATTRIBUTES (USER / AGENT)</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="reqSubjectRole" class="form-control bg-black text-white border-secondary text-xs" placeholder="Role (e.g. admin, dev)" value="admin">
                        </div>
                        <div class="col-3">
                            <input type="number" id="reqSubjectClearance" class="form-control bg-black text-white border-secondary text-xs" placeholder="Clearance (1-5)" value="4">
                        </div>
                        <div class="col-3">
                            <select id="reqSubjectMfa" class="form-select bg-black text-white border-secondary text-xs">
                                <option value="true" selected>MFA: Yes</option>
                                <option value="false">MFA: No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Resource & Action Attributes -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">2. RESOURCE &amp; ACTION ATTRIBUTES</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="reqResourceType" class="form-control bg-black text-white border-secondary text-xs" placeholder="Resource Type" value="vault_secret">
                        </div>
                        <div class="col-6">
                            <select id="reqAction" class="form-select bg-black text-white border-secondary text-xs">
                                <option value="read" selected>Action: READ</option>
                                <option value="write">Action: WRITE</option>
                                <option value="deploy">Action: DEPLOY</option>
                                <option value="terminate">Action: TERMINATE</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Environment Context -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">3. ENVIRONMENT CONTEXT</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="reqEnvIp" class="form-control bg-black text-white border-secondary text-xs" placeholder="Client IP (e.g. 10.1.2.3)" value="10.1.2.3">
                        </div>
                        <div class="col-6">
                            <input type="number" id="reqEnvTrust" class="form-control bg-black text-white border-secondary text-xs" placeholder="Trust Score (0-100)" value="95">
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary fw-bold w-100" onclick="evaluateAbacRequest()">
                    <i class="bi bi-shield-lock-fill me-1"></i> Evaluate Zero-Trust Decision
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Decision Output & Evaluation Trace -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-warning"><i class="bi bi-journal-check me-2"></i>Policy Decision &amp; Audit Trace</span>
                <span class="badge bg-secondary" id="matchedPolicyBadge">RULE: NONE</span>
            </div>
            <div class="card-body">
                
                <!-- Decision Badge Area -->
                <div id="decisionResultBanner" class="p-3 bg-black border border-success rounded text-center mb-3">
                    <div class="text-muted small fw-bold">ACCESS DECISION</div>
                    <div class="fs-2 fw-bold text-success" id="decisionText">PERMIT</div>
                    <div class="text-xs text-muted mt-1" id="decisionReason">All attribute conditions verified against Zero-Trust policy rules.</div>
                </div>

                <label class="form-label text-muted small fw-bold">EVALUATION TRACE TIMELINE</label>
                <div id="evaluationTraceContainer" class="space-y-1 p-2 bg-black border border-secondary rounded small" style="max-height: 200px; overflow-y: auto; font-family: monospace;">
                    <div class="text-muted">Click 'Evaluate Zero-Trust Decision' to view rule execution trace.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Active Policy Rules Repository -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6"><i class="bi bi-card-checklist me-2 text-info"></i>Active Zero-Trust ABAC Policy Store</span>
        <button class="btn btn-xs btn-outline-secondary" onclick="loadAbacPolicies()"><i class="bi bi-arrow-clockwise me-1"></i>Reload</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle small">
                <thead class="table-secondary text-uppercase text-muted">
                    <tr>
                        <th>Policy ID &amp; Description</th>
                        <th>Target Resource</th>
                        <th>Actions</th>
                        <th>Attribute Rules</th>
                        <th style="width: 110px;">Effect</th>
                    </tr>
                </thead>
                <tbody id="abacPolicyTableBody">
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Loading active policies...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadAbacPolicies() {
    try {
        const res = await apiFetch('/abac/policies');
        if (res && res.success) {
            document.getElementById('metricPolicyCount').innerText = `${res.data.total_policies} POLICIES`;
            renderPolicyTable(res.data.policies || []);
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Failed to load ABAC policies: ' + e.message, 'error');
    }
}

function renderPolicyTable(policies) {
    const tbody = document.getElementById('abacPolicyTableBody');
    if (!policies || policies.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No policies loaded.</td></tr>';
        return;
    }

    tbody.innerHTML = policies.map(p => `
        <tr>
            <td>
                <span class="fw-bold text-white">${escapeHtml(p.id)}</span>
                <span class="text-muted d-block text-xs">${escapeHtml(p.title || '')}</span>
            </td>
            <td><span class="badge bg-secondary">${escapeHtml((p.target.resource_type || []).join(', '))}</span></td>
            <td><span class="badge bg-info text-dark">${escapeHtml((p.target.actions || []).join(', '))}</span></td>
            <td>
                <div class="text-xs text-muted">
                    ${(p.rules || []).map(r => `• ${escapeHtml(r.category)}.${escapeHtml(r.attribute)} ${escapeHtml(r.operator)} ${escapeHtml(JSON.stringify(r.value))}`).join('<br>')}
                </div>
            </td>
            <td><span class="badge bg-${p.effect === 'PERMIT' ? 'success' : 'danger'}">${escapeHtml(p.effect)}</span></td>
        </tr>
    `).join('');
}

async function evaluateAbacRequest() {
    const req = {
        subject: {
            role: document.getElementById('reqSubjectRole').value,
            clearance_level: parseInt(document.getElementById('reqSubjectClearance').value) || 1,
            mfa_verified: document.getElementById('reqSubjectMfa').value === 'true',
            is_authenticated: true
        },
        resource: {
            type: document.getElementById('reqResourceType').value
        },
        action: document.getElementById('reqAction').value,
        environment: {
            ip_address: document.getElementById('reqEnvIp').value,
            device_trust_score: parseInt(document.getElementById('reqEnvTrust').value) || 0
        }
    };

    try {
        const res = await apiFetch('/abac/evaluate', {
            method: 'POST',
            body: JSON.stringify(req)
        });

        if (res && res.success) {
            const data = res.data;
            const isPermit = data.decision === 'PERMIT';

            document.getElementById('decisionText').innerText = data.decision;
            document.getElementById('decisionText').className = `fs-2 fw-bold text-${isPermit ? 'success' : 'danger'}`;
            document.getElementById('decisionResultBanner').className = `p-3 bg-black border border-${isPermit ? 'success' : 'danger'} rounded text-center mb-3`;
            document.getElementById('matchedPolicyBadge').innerText = `RULE: ${data.matched_policy || 'NONE'}`;

            const traceContainer = document.getElementById('evaluationTraceContainer');
            traceContainer.innerHTML = (data.evaluation_trace || []).map(t => `
                <div class="p-1 rounded bg-black border border-${t.status === 'MATCHED' ? 'success' : 'secondary'} mb-1">
                    <span class="badge bg-${t.status === 'MATCHED' ? 'success' : (t.status === 'NOT_APPLICABLE' ? 'secondary' : 'danger')}">${t.status}</span>
                    <span class="text-white ms-1 fw-bold">${escapeHtml(t.policy_id)}:</span>
                    <span class="text-muted ms-1">${escapeHtml(t.reason)}</span>
                </div>
            `).join('');

            if (typeof showToast === 'function') showToast(`Access Decision: ${data.decision}`, isPermit ? 'success' : 'warning');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Evaluation error: ' + e.message, 'error');
    }
}

async function runAbacBatchSimulation() {
    try {
        const res = await apiFetch('/abac/simulate', { method: 'POST', body: JSON.stringify({}) });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Simulated ${res.data.total_scenarios} zero-trust scenarios successfully`, 'success');
            evaluateAbacRequest();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Simulation error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadAbacPolicies();
    evaluateAbacRequest();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
