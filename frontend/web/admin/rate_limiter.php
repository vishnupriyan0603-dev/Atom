<?php
// ATOM Web Admin — Phase 56: Multi-Tenant Zero-Trust Rate Limiter & Token-Bucket Mesh
$pageTitle = "API Rate Limiter (Phase 56)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F43F5E;">Multi-Tenant Zero-Trust Token Bucket Rate Limiter</h2>
        <p class="text-muted small mb-0">Phase 56: Sliding-Window Continuous Token Refill, DDoS Mitigation, HTTP 429 Retry-After &amp; Tenant Quota Manager</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-danger fw-bold" onclick="simulateBurstTraffic()">
            <i class="bi bi-lightning-fill me-1"></i> Simulate Burst (10 Req)
        </button>
    </div>
</div>

<!-- Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE CLIENT BUCKETS</div>
            <div class="fs-4 fw-bold text-info" id="metricActiveClients">1 ACTIVE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">REMAINING TOKENS</div>
            <div class="fs-4 fw-bold text-success" id="metricRemainingTokens">60 / 60</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">RATE LIMIT STATUS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricStatus" style="color: #34D399;">ALLOWED (200 OK)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">THROTTLE ALGORITHM</div>
            <div class="fs-4 fw-bold text-warning">Continuous Token Bucket</div>
        </div>
    </div>
</div>

<!-- Interactive Testing Console -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-danger"><i class="bi bi-speedometer2 me-2"></i>Token Consumption Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CLIENT IDENTIFIER (TENANT / IP / TOKEN)</label>
                    <input type="text" id="clientIdInput" class="form-control bg-black text-white border-secondary" value="tenant_enterprise_01">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SUBSCRIPTION TIER</label>
                    <select id="tierSelect" class="form-select bg-black text-white border-secondary">
                        <option value="tier_enterprise">Tier Enterprise (600 RPM - Burst 600)</option>
                        <option value="default" selected>Default Tier (60 RPM - Burst 60)</option>
                        <option value="tier_free">Tier Free (20 RPM - Burst 20)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TOKENS TO CONSUME</label>
                    <input type="number" id="tokensInput" class="form-control bg-black text-white border-secondary" value="1" min="1" max="100">
                </div>
                <button class="btn btn-danger fw-bold w-100" onclick="consumeTokens()">
                    <i class="bi bi-play-fill me-1"></i> Consume Token &amp; Check Rate Limit
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-emerald-400" style="color: #34D399;"><i class="bi bi-shield-lock-fill me-2"></i>Rate Limit Decision &amp; HTTP Headers</span>
            </div>
            <div class="card-body">
                <pre id="rateLimitResultDisplay" class="bg-black p-3 rounded text-emerald-400 border border-secondary small" style="font-family: monospace; color: #34D399; height: 190px; overflow-y: auto;">Click 'Consume Token' to test rate limiting.</pre>
            </div>
        </div>
    </div>
</div>

<script>
async function consumeTokens(customTokens = null) {
    const clientId = document.getElementById('clientIdInput').value;
    const tier = document.getElementById('tierSelect').value;
    const tokens = customTokens !== null ? customTokens : parseInt(document.getElementById('tokensInput').value, 10);

    try {
        const res = await apiFetch('/rate-limiter/check', {
            method: 'POST',
            body: JSON.stringify({ client_id: clientId, tier: tier, tokens: tokens })
        });

        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricRemainingTokens').innerText = `${data.remaining} / ${data.limit}`;
            document.getElementById('metricStatus').innerText = data.allowed ? 'ALLOWED (200 OK)' : `THROTTLED (429 Retry in ${data.retry_after_sec}s)`;
            document.getElementById('metricStatus').className = `fs-4 fw-bold text-${data.allowed ? 'emerald-400' : 'danger'}`;

            document.getElementById('rateLimitResultDisplay').innerText = JSON.stringify({
                "HTTP/1.1": data.allowed ? "200 OK" : "429 Too Many Requests",
                "X-RateLimit-Limit": data.limit,
                "X-RateLimit-Remaining": data.remaining,
                "Retry-After": data.retry_after_sec,
                "Client-ID": data.client_id,
                "Decision": data.status
            }, null, 2);

            if (typeof showToast === 'function') showToast(data.allowed ? 'Request permitted within quota' : `Throttled! Retry in ${data.retry_after_sec}s`, data.allowed ? 'success' : 'error');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Rate limit error: ' + e.message, 'error');
    }
}

async function simulateBurstTraffic() {
    for (let i = 0; i < 10; i++) {
        await consumeTokens(1);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    consumeTokens(0);
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
