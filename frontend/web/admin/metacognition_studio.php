<?php
// ATOM Web Admin — Phase 80 Landmark Milestone: Autonomous AI Agent Self-Reflection, Thought-Graph Pruning & Metacognitive Reasoning Crossbar
$pageTitle = "Metacognitive Brain (Phase 80 Landmark)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #8B5CF6;">Metacognitive Self-Reflection &amp; Thought-Graph Pruner</h2>
        <p class="text-muted small mb-0">Phase 80 Landmark Milestone: Autonomous Step-by-Step Reason Critique, Circular Loop Detection, Hallucination Prevention &amp; Graph Pruning</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: #8B5CF6;" onclick="runMetacognitiveReflection()">
            <i class="bi bi-cpu me-1"></i> Reflect on Thought Chain
        </button>
    </div>
</div>

<!-- Metacognitive Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">METACOGNITIVE CLARITY</div>
            <div class="fs-4 fw-bold text-purple-400" id="metricClarity" style="color: #A78BFA;">95.0% (RIGOROUS)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STEPS EVALUATED</div>
            <div class="fs-4 fw-bold text-info" id="metricSteps">4 STEPS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DETECTED FLAWS / LOOPS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricFlaws" style="color: #34D399;">0 FLAWS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">REASONING STATUS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricStatus">RIGOROUS</div>
        </div>
    </div>
</div>

<!-- Thought Chain Input & Step Critique Matrix -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-purple-400"><i class="bi bi-pencil-square me-2"></i>Thought Chain Input Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TARGET PROBLEM / GOAL</label>
                    <input type="text" id="goalInput" class="form-control bg-black text-white border-secondary small" value="Zero-downtime database migration for 10M rows">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">REASONING STEPS (1 PER LINE)</label>
                    <textarea id="stepsInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="6">1. Lock table exclusively to avoid race conditions
2. Copy 10M rows in a single batch transaction
3. Lock table exclusively to avoid race conditions
4. Verify checksum of migrated table</textarea>
                </div>

                <button class="btn btn-sm text-white fw-bold w-100 mb-3" style="background: #8B5CF6;" onclick="runMetacognitiveReflection()">
                    <i class="bi bi-play-circle-fill me-1"></i> Evaluate Reasoning Rigor &amp; Prune Graph
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-check2-circle me-2"></i>Metacognitive Critique &amp; Confidence</span>
                <span class="badge bg-secondary" id="clarityBadge">CLARITY: 95%</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>#</th>
                                <th>Step / Thought</th>
                                <th>Score</th>
                                <th>Critique</th>
                            </tr>
                        </thead>
                        <tbody id="stepsTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Loading critique...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function runMetacognitiveReflection() {
    const goal = document.getElementById('goalInput').value.trim();
    const rawSteps = document.getElementById('stepsInput').value.split('\n').map(s => s.trim()).filter(s => s.length > 0);

    try {
        const res = await apiFetch('/brain/metacognition/reflect', {
            method: 'POST',
            body: JSON.stringify({ goal: goal, steps: rawSteps })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricClarity').innerText = `${d.metacognitive_clarity_pct}% (${d.status})`;
            document.getElementById('metricSteps').innerText = `${d.total_steps_evaluated} STEPS`;
            document.getElementById('metricFlaws').innerText = `${d.flaws_count} FLAWS`;
            document.getElementById('metricStatus').innerText = d.status;
            document.getElementById('clarityBadge').innerText = `CLARITY: ${d.metacognitive_clarity_pct}%`;

            renderStepsTable(d.steps || []);
            if (typeof showToast === 'function') {
                showToast(`Metacognition: Clarity ${d.metacognitive_clarity_pct}% (${d.flaws_count} flaws)`, d.flaws_count === 0 ? 'success' : 'warning');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Reflection error: ' + e.message, 'error');
    }
}

function renderStepsTable(steps) {
    const tbody = document.getElementById('stepsTableBody');
    if (!steps || steps.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">No steps evaluated.</td></tr>`;
        return;
    }

    tbody.innerHTML = steps.map(s => `
        <tr>
            <td class="fw-bold">${s.step_number}</td>
            <td class="text-white">${escapeHtml(s.thought)}</td>
            <td><span class="badge ${s.confidence >= 0.8 ? 'bg-success' : 'bg-danger'}">${(s.confidence * 100).toFixed(0)}%</span></td>
            <td class="text-xs text-muted">${escapeHtml(s.critique)}</td>
        </tr>
    `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    runMetacognitiveReflection();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
