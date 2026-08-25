<?php
// ATOM Web Admin — Phase 71: Autonomous Canary Deployment & Traffic Split Governor
$pageTitle = "Canary Governor (Phase 71)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #A855F7;">Autonomous Canary Deployment Governor</h2>
        <p class="text-muted small mb-0">Phase 71: Fine-Grained Traffic Splitting ($90/10$), Automated Error Rate Circuit Breaker &amp; Zero-Downtime Rollback</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-danger text-white fw-bold" onclick="emergencyRollback()">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> Emergency Rollback (0%)
        </button>
    </div>
</div>

<!-- Canary Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DEPLOYMENT STATUS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricStatus" style="color: #34D399;">HEALTHY</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TRAFFIC SPLIT RATIO</div>
            <div class="fs-4 fw-bold text-purple-400" id="metricSplit" style="color: #A855F7;">90% / 10%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CANARY ERROR RATE</div>
            <div class="fs-4 fw-bold text-info" id="metricErrorRate">0.0% (SLO OK)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CANARY VERSION</div>
            <div class="fs-4 fw-bold text-warning" id="metricVersion">v1.5.0-canary</div>
        </div>
    </div>
</div>

<!-- Main Traffic Split Adjuster & Simulation Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-purple-400"><i class="bi bi-sliders me-2"></i>Adjust Canary Traffic Weight</span>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>CANARY TRAFFIC PERCENTAGE</span>
                        <span class="text-purple-400 fw-bold" id="sliderValueText">10%</span>
                    </label>
                    <input type="range" class="form-range" min="0" max="100" step="5" value="10" id="canaryWeightSlider" oninput="updateSliderText(this.value)">
                </div>

                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-xs btn-outline-secondary" onclick="setWeight(0)">0% (Disable)</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="setWeight(10)">10% (Canary)</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="setWeight(25)">25% (Ramp)</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="setWeight(50)">50% (Split)</button>
                    <button class="btn btn-xs btn-outline-success" onclick="setWeight(100)">100% (Promote)</button>
                </div>

                <button class="btn btn-sm btn-purple text-white fw-bold w-100" style="background: #A855F7;" onclick="applyCanaryWeight()">
                    <i class="bi bi-check-circle-fill me-1"></i> Apply Traffic Weights
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-hdd-network me-2"></i>Live Traffic Routing Simulator</span>
                <button class="btn btn-xs btn-outline-info" onclick="simulateLiveTraffic()"><i class="bi bi-play-fill me-1"></i>Burst 20 Requests</button>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted mb-3 font-monospace" id="routingLogBox" style="max-height: 180px; overflow-y: auto;">
                    [Ready] Click 'Burst 20 Requests' to observe real-time hash-weighted routing across stable vs. canary versions...
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted">
                    <i class="bi bi-shield-lock-fill text-purple-400 me-1"></i> If canary error rate exceeds 5%, the circuit breaker instantly trips and shifts 100% of traffic back to stable.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateSliderText(val) {
    document.getElementById('sliderValueText').innerText = `${val}%`;
}

function setWeight(val) {
    document.getElementById('canaryWeightSlider').value = val;
    updateSliderText(val);
    applyCanaryWeight();
}

async function loadCanaryStatus() {
    try {
        const res = await apiFetch('/infrastructure/canary/status');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricStatus').innerText = d.circuit_tripped ? 'ROLLED BACK' : 'HEALTHY';
            document.getElementById('metricStatus').className = `fs-4 fw-bold ${d.circuit_tripped ? 'text-danger' : 'text-emerald-400'}`;
            document.getElementById('metricSplit').innerText = `${d.stable_weight_pct}% / ${d.canary_weight_pct}%`;
            document.getElementById('metricErrorRate').innerText = `${d.error_rate_pct}% (${d.canary_errors} errors)`;
            document.getElementById('metricVersion').innerText = d.canary_version;
            document.getElementById('canaryWeightSlider').value = d.canary_weight_pct;
            updateSliderText(d.canary_weight_pct);
        }
    } catch (e) {
        console.error(e);
    }
}

async function applyCanaryWeight() {
    const val = parseInt(document.getElementById('canaryWeightSlider').value, 10);
    try {
        const res = await apiFetch('/infrastructure/canary/update-weights', {
            method: 'POST',
            body: JSON.stringify({ canary_weight_pct: val })
        });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Canary weight updated to ${val}%`, 'success');
            loadCanaryStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Weight update error: ' + e.message, 'error');
    }
}

async function emergencyRollback() {
    setWeight(0);
}

async function simulateLiveTraffic() {
    const logBox = document.getElementById('routingLogBox');
    logBox.innerHTML = '[Traffic Burst Initiated]<br>';

    let stableCount = 0;
    let canaryCount = 0;

    for (let i = 0; i < 20; i++) {
        const reqId = 'req_' + Math.random().toString(36).substring(7);
        const res = await apiFetch('/infrastructure/canary/route', {
            method: 'POST',
            body: JSON.stringify({ request_id: reqId })
        });

        if (res && res.success) {
            const r = res.data;
            if (r.is_canary) canaryCount++; else stableCount++;
            const badge = r.is_canary ? '<span class="text-warning fw-bold">[CANARY]</span>' : '<span class="text-emerald-400">[STABLE]</span>';
            logBox.innerHTML += `Request #${i+1} -> ${badge} (${r.target_version})<br>`;
        }
    }

    logBox.innerHTML += `<strong>Summary:</strong> ${stableCount} routed to Stable, ${canaryCount} routed to Canary.<br>`;
    logBox.scrollTop = logBox.scrollHeight;
    loadCanaryStatus();
}

document.addEventListener('DOMContentLoaded', () => {
    loadCanaryStatus();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
