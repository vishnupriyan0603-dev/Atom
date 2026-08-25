<?php
// ATOM Web Admin — Phase 55: Zero-Knowledge Federated Learning & Differential Privacy Noise Mesh
$pageTitle = "Federated Learning Mesh (Phase 55)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #10B981;">Zero-Knowledge Federated Learning &amp; Differential Privacy Mesh</h2>
        <p class="text-muted small mb-0">Phase 55: Decentralized Model Weight Aggregation (FedAvg), $(\epsilon, \delta)$ Laplacian Privacy Noise &amp; Zero-Trust Edge Collaboration</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-success fw-bold" onclick="runFederatedAggregation()">
            <i class="bi bi-diagram-3-fill me-1"></i> Aggregate Edge Weights
        </button>
    </div>
</div>

<!-- Federated Learning Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PRIVACY BUDGET</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">&epsilon; = 0.50 (Strict DP)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EDGE NODES</div>
            <div class="fs-4 fw-bold text-info" id="metricNodesCount">2 CONTRIBUTING NODES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AGGREGATION ALGORITHM</div>
            <div class="fs-4 fw-bold text-warning">FedAvg + Laplace Noise</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DATA LEAKAGE RISK</div>
            <div class="fs-4 fw-bold text-success">0.0% (Zero Raw Data)</div>
        </div>
    </div>
</div>

<!-- Model Weight Inspection -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6"><i class="bi bi-cpu-fill me-2 text-emerald-400"></i>Global Model Tensor Weights (Post-Privacy Aggregation)</span>
        <span class="badge bg-success" id="trainingRoundBadge">ROUND_001</span>
    </div>
    <div class="card-body p-3">
        <pre id="globalWeightsDisplay" class="bg-black p-3 rounded text-emerald-400 border border-secondary small mb-0" style="font-family: monospace; color: #34D399;">Loading global weights...</pre>
    </div>
</div>

<script>
async function loadGlobalWeights() {
    try {
        const res = await apiFetch('/federated-learning/weights');
        if (res && res.success) {
            document.getElementById('globalWeightsDisplay').innerText = JSON.stringify(res.data.global_weights, null, 2);
        }
    } catch (e) {
        console.error(e);
    }
}

async function runFederatedAggregation() {
    try {
        const res = await apiFetch('/federated-learning/aggregate', {
            method: 'POST',
            body: JSON.stringify({})
        });
        if (res && res.success) {
            document.getElementById('globalWeightsDisplay').innerText = JSON.stringify(res.data.global_weights, null, 2);
            document.getElementById('trainingRoundBadge').innerText = res.data.training_round;
            document.getElementById('metricNodesCount').innerText = `${res.data.participating_nodes} CONTRIBUTING NODES`;
            if (typeof showToast === 'function') showToast('Federated model weights aggregated with differential privacy!', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Aggregation error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadGlobalWeights();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
