<?php
// ATOM Web Admin — Phase 89: Autonomous API Rate Limiting Cost Governor & Token Budget Allocator
$pageTitle = "LLM Cost Governor (Phase 89)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #10B981;">AI Token Cost Governor &amp; Budget Allocator</h2>
        <p class="text-muted small mb-0">Phase 89: Multi-Model LLM Pricing Accounting, Tenant Budget Hard Caps, Soft-Alert Warnings &amp; Zero-Cost Local Fallback</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-success text-dark fw-bold" onclick="trackSpendDemo()">
            <i class="bi bi-cash-coin me-1"></i> Simulate LLM Call
        </button>
    </div>
</div>

<!-- Cost Governor Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL TENANTS GOVERNED</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricTenants" style="color: #34D399;">3 TENANTS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CURRENT PLATFORM SPEND</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricTotalSpend">$1.48 USD</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">LOCAL ZERO-COST SLM</div>
            <div class="fs-4 fw-bold text-cyan-400">atom-neural-edge</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">THROTTLED TENANTS</div>
            <div class="fs-4 fw-bold text-danger" id="metricThrottled">0 BLOCKED</div>
        </div>
    </div>
</div>

<!-- Budgets Table & Simulator Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-wallet2 text-emerald-400 me-2"></i>Tenant LLM Budgets &amp; Usage</span>
                <span class="badge bg-secondary" id="budgetsBadge">3 TENANTS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Tenant</th>
                                <th>Current Spend</th>
                                <th>Monthly Cap</th>
                                <th>Spend %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="budgetsTableBody">
                            <tr><td colspan="5" class="text-center p-3 text-muted">Loading budgets...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-emerald-400"><i class="bi bi-cpu me-2"></i>LLM Token Usage Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SELECT TENANT</label>
                    <select id="tenantSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="tenant_acme_corp">tenant_acme_corp ($250 Cap)</option>
                        <option value="tenant_startup_inc">tenant_startup_inc ($50 Cap)</option>
                        <option value="tenant_research_lab">tenant_research_lab ($500 Cap)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">AI MODEL TIER</label>
                    <select id="modelSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="gpt-4o">gpt-4o ($2.50 / $10.00)</option>
                        <option value="claude-3-5-sonnet">claude-3-5-sonnet ($3.00 / $15.00)</option>
                        <option value="gemini-1-5-pro">gemini-1-5-pro ($1.25 / $5.00)</option>
                        <option value="atom-neural-edge">atom-neural-edge ($0.00 Zero-Cost)</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">PROMPT TOKENS</label>
                        <input type="number" id="promptTokensInput" class="form-control bg-black text-white border-secondary small" value="25000">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">COMPLETION</label>
                        <input type="number" id="completionTokensInput" class="form-control bg-black text-white border-secondary small" value="8000">
                    </div>
                </div>

                <button class="btn btn-sm btn-success text-dark fw-bold w-100 mb-3" onclick="trackSpendDemo()">
                    <i class="bi bi-arrow-right-circle-fill me-1"></i> Track &amp; Verify Budget
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="spendResultBox">
                    [Ready] Dispatch test token burns to verify real-time budget enforcement...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadBudgets() {
    try {
        const res = await apiFetch('/ai/governor/budgets');
        if (res && res.success) {
            const list = res.data.budgets || [];
            let totalSpend = 0;
            let throttled = 0;

            list.forEach(t => {
                totalSpend += t.current_spend_usd;
                if (t.status === 'THROTTLED_BUDGET_EXCEEDED') throttled++;
            });

            document.getElementById('metricTenants').innerText = `${list.length} TENANTS`;
            document.getElementById('metricTotalSpend').innerText = `$${totalSpend.toFixed(2)} USD`;
            document.getElementById('metricThrottled').innerText = `${throttled} BLOCKED`;
            document.getElementById('budgetsBadge').innerText = `${list.length} TENANTS`;

            renderBudgetsTable(list);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderBudgetsTable(budgets) {
    const tbody = document.getElementById('budgetsTableBody');
    if (!budgets || budgets.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted p-3">No budgets found.</td></tr>`;
        return;
    }

    tbody.innerHTML = budgets.map(b => {
        let badgeClass = 'bg-success';
        if (b.status === 'THROTTLED_BUDGET_EXCEEDED') badgeClass = 'bg-danger';
        else if (b.status === 'WARNING_80_PCT') badgeClass = 'bg-warning text-dark';

        return `
            <tr>
                <td class="fw-bold text-white"><i class="bi bi-building me-2 text-emerald-400"></i>${escapeHtml(b.tenant_id)}</td>
                <td class="text-white fw-bold">$${b.current_spend_usd.toFixed(2)}</td>
                <td class="text-muted">$${b.monthly_limit_usd.toFixed(2)}</td>
                <td>
                    <div class="progress bg-secondary" style="height: 6px;">
                        <div class="progress-bar ${b.spend_pct >= 100 ? 'bg-danger' : (b.spend_pct >= 80 ? 'bg-warning' : 'bg-success')}" style="width: ${Math.min(100, b.spend_pct)}%;"></div>
                    </div>
                    <span class="text-xs text-muted">${b.spend_pct}%</span>
                </td>
                <td><span class="badge ${badgeClass}">${escapeHtml(b.status)}</span></td>
            </tr>
        `;
    }).join('');
}

async function trackSpendDemo() {
    const tenant = document.getElementById('tenantSelect').value;
    const model = document.getElementById('modelSelect').value;
    const prompt = parseInt(document.getElementById('promptTokensInput').value, 10) || 1000;
    const comp = parseInt(document.getElementById('completionTokensInput').value, 10) || 500;

    try {
        const res = await apiFetch('/ai/governor/track', {
            method: 'POST',
            body: JSON.stringify({
                tenant_id: tenant,
                model: model,
                prompt_tokens: prompt,
                completion_tokens: comp
            })
        });

        if (res && res.success) {
            const d = res.data;
            const statusClass = d.allowed ? 'text-emerald-400' : 'text-danger';

            document.getElementById('spendResultBox').innerHTML = `
                <div class="${statusClass} fw-bold mb-1">[${d.status}] Call Cost: $${d.call_cost_usd}</div>
                <div class="text-white text-xs mb-1"><strong>Spend:</strong> $${d.current_spend_usd} / $${d.monthly_limit_usd}</div>
                <div class="text-muted text-xs"><strong>Advisor:</strong> ${escapeHtml(d.recommendation)}</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Tracked $${d.call_cost_usd} for ${d.tenant_id}`, d.allowed ? 'success' : 'error');
            }
            loadBudgets();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Tracking error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadBudgets();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
