<?php
// ATOM Web Admin — Phase 60: Real-Time Distributed Tracing & OpenTelemetry W3C Span Mesh (Landmark Milestone)
$pageTitle = "Distributed Tracing (Phase 60)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="background: linear-gradient(135deg, #06B6D4 0%, #3B82F6 50%, #8B5CF6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            🌌 Real-Time Distributed Tracing &amp; Span Mesh
        </h2>
        <p class="text-muted small mb-0">Phase 60 Milestone: W3C `traceparent` Context Propagation, OpenTelemetry Hierarchical Flamegraph &amp; Cross-Subsystem Latency Tracing</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-cyan text-white fw-bold" style="background: #06B6D4;" onclick="simulateCrossbarTrace()">
            <i class="bi bi-play-circle-fill me-1"></i> Simulate Crossbar Trace
        </button>
    </div>
</div>

<!-- Tracing Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL TRACE TIME</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricTotalDuration" style="color: #22D3EE;">7.0 ms</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TRACED SUBSYSTEMS</div>
            <div class="fs-4 fw-bold text-info" id="metricSpansCount">4 SPANS (100% OK)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PROPAGATION FORMAT</div>
            <div class="fs-4 fw-bold text-warning">W3C traceparent (00)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">OPENTELEMETRY COMPAT</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">Jaeger • Zipkin • OTel</div>
        </div>
    </div>
</div>

<!-- Main Flamegraph Span Hierarchy -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6"><i class="bi bi-water me-2 text-cyan-400"></i>Distributed Trace Flamegraph &amp; Latency Waterfall</span>
        <span class="badge bg-secondary font-monospace" id="currentTraceIdBadge">trace: 4bf92f3577b34da6a3ce929d0e0e4736</span>
    </div>
    <div class="card-body p-3">
        <div class="p-3 bg-black rounded border border-secondary mb-3">
            <div class="text-muted small fw-bold mb-2">ROOT OPERATION: <span class="text-white" id="rootTraceTitle">ATOM Crossbar Command Dispatch</span></div>
            <div class="space-y-3" id="flamegraphContainer">
                <!-- Span 1 -->
                <div class="p-2 rounded bg-[#111827] border border-cyan-500/30">
                    <div class="d-flex justify-content-between text-xs mb-1">
                        <span class="fw-bold text-cyan-400">Gateway Ingest (Orchestration Gateway)</span>
                        <span class="text-muted">1.2 ms</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-cyan-400" style="width: 17%; background: #22D3EE;"></div>
                    </div>
                </div>

                <!-- Span 2 -->
                <div class="p-2 rounded bg-[#111827] border border-blue-500/30 ms-4">
                    <div class="d-flex justify-content-between text-xs mb-1">
                        <span class="fw-bold text-blue-400">ABAC Zero-Trust Evaluation (ABAC Firewall)</span>
                        <span class="text-muted">0.8 ms</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-blue-500" style="width: 11%;"></div>
                    </div>
                </div>

                <!-- Span 3 -->
                <div class="p-2 rounded bg-[#111827] border border-rose-500/30 ms-4">
                    <div class="d-flex justify-content-between text-xs mb-1">
                        <span class="fw-bold text-rose-400">Token Bucket Rate Limit Check (Rate Limiter)</span>
                        <span class="text-muted">0.4 ms</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-rose-500" style="width: 6%;"></div>
                    </div>
                </div>

                <!-- Span 4 -->
                <div class="p-2 rounded bg-[#111827] border border-pink-500/30 ms-4">
                    <div class="d-flex justify-content-between text-xs mb-1">
                        <span class="fw-bold text-pink-400">Spectral Audio Denoising &amp; Formant Shift (Voice Engine)</span>
                        <span class="text-muted">4.6 ms</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-pink-500" style="width: 66%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadTraces() {
    try {
        const res = await apiFetch('/tracing/traces');
        if (res && res.success && res.data.traces.length > 0) {
            const trace = res.data.traces[0];
            document.getElementById('currentTraceIdBadge').innerText = `trace: ${trace.trace_id}`;
            document.getElementById('rootTraceTitle').innerText = trace.root_name;
            renderFlamegraph(trace.spans || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderFlamegraph(spans) {
    const container = document.getElementById('flamegraphContainer');
    if (!spans || spans.length === 0) return;

    let totalDuration = 0;
    spans.forEach(s => totalDuration += (s.duration_ms || 1.0));
    document.getElementById('metricTotalDuration').innerText = `${totalDuration.toFixed(1)} ms`;
    document.getElementById('metricSpansCount').innerText = `${spans.length} SPANS (100% OK)`;

    container.innerHTML = spans.map((s, idx) => {
        const pct = Math.max(5, Math.min(100, Math.round(((s.duration_ms || 1.0) / Math.max(1, totalDuration)) * 100)));
        const indentClass = s.parent_id ? 'ms-4' : '';
        const color = idx === 0 ? '#22D3EE' : (idx === 1 ? '#3B82F6' : (idx === 2 ? '#F43F5E' : '#EC4899'));

        return `
            <div class="p-2 rounded bg-[#111827] border border-secondary ${indentClass}">
                <div class="d-flex justify-content-between text-xs mb-1">
                    <span class="fw-bold" style="color: ${color};">${escapeHtml(s.name)} (${escapeHtml(s.subsystem)})</span>
                    <span class="text-muted">${s.duration_ms} ms</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" style="width: ${pct}%; background: ${color};"></div>
                </div>
            </div>
        `;
    }).join('');
}

async function simulateCrossbarTrace() {
    try {
        const res = await apiFetch('/tracing/spans/start', {
            method: 'POST',
            body: JSON.stringify({ name: 'Live Command Crossbar Execution', subsystem: 'CrossbarGateway' })
        });
        if (res && res.success) {
            const spanId = res.data.span_id;
            await new Promise(r => setTimeout(r, 10));
            await apiFetch('/tracing/spans/end', {
                method: 'POST',
                body: JSON.stringify({ span_id: spanId, status: 'OK' })
            });

            if (typeof showToast === 'function') showToast('Crossbar trace simulation finished and recorded!', 'success');
            loadTraces();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Trace error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadTraces();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
