<?php
// ATOM Web Admin — Phase 43: Codebase Dependency Graph & Circular Reference Resolver
$pageTitle = "Dependency Graph & Circular Resolver (Phase 43)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #A855F7;">Codebase Dependency Graph &amp; Architecture Modernizer</h2>
        <p class="text-muted small mb-0">Phase 43: Directed Dependency Network Graphing, Martin Metric Coupling Analysis, Circular Reference Detection &amp; Automated Decoupling Patch Synthesizer</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="loadDependencyGraph();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Graph
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: linear-gradient(135deg, #A855F7 0%, #7E22CE 100%); border: none;" onclick="runCycleSimulationDemo()">
            <i class="bi bi-magic me-1"></i> Simulate Circular Cycle
        </button>
    </div>
</div>

<!-- Architecture & Dependency Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL NODES / CLASSES</div>
            <div class="fs-4 fw-bold text-info" id="metricTotalNodes">0 NODES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DEPENDENCY EDGES</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricTotalEdges" style="color: #34D399;">0 EDGES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CIRCULAR CYCLES</div>
            <div class="fs-4 fw-bold text-danger" id="metricCycleBadge">0 CYCLES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ABSTRACTNESS INDEX (A)</div>
            <div class="fs-4 fw-bold text-warning" id="metricAbstractness">0.0 (Balanced)</div>
        </div>
    </div>
</div>

<!-- Main Grid -->
<div class="row g-4 mb-4">
    <!-- 1. Interactive Dependency Network Canvas -->
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-diagram-3-fill me-2"></i>Directed Dependency Network Topology</span>
                <span class="badge bg-secondary" id="graphModeBadge">DAG TOPOLOGY</span>
            </div>
            <div class="card-body p-3">
                <div class="p-3 bg-black border border-secondary rounded position-relative" style="min-height: 420px; overflow: hidden;" id="canvasWrapper">
                    <canvas id="dependencyNetworkCanvas" style="width: 100%; height: 420px; display: block;"></canvas>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 text-muted small">
                    <div>
                        <span class="badge bg-primary me-1">■ Stable Node</span>
                        <span class="badge bg-danger me-1">■ Circular Risk</span>
                        <span class="badge bg-warning text-dark">■ High Coupling</span>
                    </div>
                    <div>Click any node to inspect coupling metrics</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Circular Reference Detection & Auto-Decouple Synthesizer -->
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-danger"><i class="bi bi-arrow-repeat me-2"></i>Circular Reference Resolver</span>
                <span class="badge bg-danger" id="cycleStatusBadge">0 DETECTED</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DETECTED CIRCULAR PATH</label>
                    <div id="detectedCyclePathBox" class="p-3 bg-black border border-secondary rounded text-danger small" style="font-family: monospace; white-space: pre-wrap;">
No circular references detected. System architecture is cleanly decoupled.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DECOUPLING STRATEGY</label>
                    <select id="decoupleStrategySelect" class="form-select bg-black text-white border-secondary">
                        <option value="interface_inversion">Dependency Inversion (DIP Interface Contract)</option>
                        <option value="event_driven">Event-Driven Pub/Sub Decoupling</option>
                        <option value="mediator">Mediator Coordinator Pattern</option>
                    </select>
                </div>

                <button class="btn btn-sm btn-info text-dark fw-bold w-100 mb-3" onclick="synthesizeDecouplingPatch()">
                    <i class="bi bi-magic me-1"></i> Synthesize Automated Decoupling Patch
                </button>

                <div>
                    <label class="form-label text-muted small fw-bold">SYNTHESIZED REFACTORING PATCH</label>
                    <textarea id="decouplingPatchOutput" class="form-control bg-black text-emerald-400 border-secondary small" rows="7" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>// Refactoring patch code will appear here upon synthesis...</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. Class Coupling & Martin Instability Table -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6"><i class="bi bi-table me-2 text-warning"></i>Class Coupling, Instability (I) &amp; Distance from Main Sequence (D)</span>
        <button class="btn btn-xs btn-outline-secondary" onclick="exportMermaid()"><i class="bi bi-code-square me-1"></i>Export Mermaid.js</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle small">
                <thead class="table-secondary text-uppercase text-muted">
                    <tr>
                        <th>Class / Module</th>
                        <th style="width: 140px;">Afferent ($C_a$)</th>
                        <th style="width: 140px;">Efferent ($C_e$)</th>
                        <th style="width: 140px;">Instability ($I$)</th>
                        <th style="width: 140px;">Distance ($D$)</th>
                        <th style="width: 130px;">Stability Class</th>
                        <th style="width: 110px;">Risk Level</th>
                    </tr>
                </thead>
                <tbody id="couplingTableBody">
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Loading dependency metrics...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let currentGraphData = null;
let simulatedCycleActive = false;

async function loadDependencyGraph(customGraph = null) {
    try {
        let res;
        if (customGraph) {
            res = await apiFetch('/dependency/scan', {
                method: 'POST',
                body: JSON.stringify({ graph: customGraph })
            });
        } else {
            res = await apiFetch('/dependency/graph');
        }

        if (res && res.success) {
            currentGraphData = res.data;
            renderGraphMetrics(currentGraphData);
            renderCouplingTable(currentGraphData.nodes);
            drawNetworkCanvas(currentGraphData);
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Failed to load dependency graph: ' + e.message, 'error');
    }
}

function renderGraphMetrics(data) {
    document.getElementById('metricTotalNodes').innerText = `${data.total_nodes} NODES`;
    document.getElementById('metricTotalEdges').innerText = `${data.total_edges} EDGES`;
    document.getElementById('metricAbstractness').innerText = `${(data.abstractness_index * 100).toFixed(1)}%`;

    const cycles = data.circular_cycles || [];
    document.getElementById('metricCycleBadge').innerText = `${cycles.length} CYCLES`;
    document.getElementById('cycleStatusBadge').innerText = `${cycles.length} DETECTED`;
    document.getElementById('cycleStatusBadge').className = `badge bg-${cycles.length > 0 ? 'danger' : 'success'}`;

    const cycleBox = document.getElementById('detectedCyclePathBox');
    if (cycles.length > 0) {
        cycleBox.innerText = cycles.map((c, i) => `⚠️ [Cycle #${i + 1}] ${c.join('  ➔  ')}`).join('\n\n');
        cycleBox.className = 'p-3 bg-black border border-danger rounded text-danger small';
    } else {
        cycleBox.innerText = '✨ No circular references detected. System architecture is cleanly decoupled.';
        cycleBox.className = 'p-3 bg-black border border-success rounded text-success small';
    }
}

function renderCouplingTable(nodes) {
    const tbody = document.getElementById('couplingTableBody');
    const entries = Object.entries(nodes || {});

    if (entries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No nodes mapped.</td></tr>';
        return;
    }

    tbody.innerHTML = entries.map(([className, m]) => `
        <tr>
            <td>
                <span class="fw-bold text-white">${escapeHtml(className)}</span>
                <span class="text-muted ms-2 text-xs">(${escapeHtml(m.meta ? m.meta.type : 'class')})</span>
            </td>
            <td><span class="badge bg-secondary">${m.afferent_coupling} incoming</span></td>
            <td><span class="badge bg-secondary">${m.efferent_coupling} outgoing</span></td>
            <td><span class="fw-bold text-info">${m.instability_index}</span></td>
            <td><span class="fw-bold text-warning">${m.distance_from_main_sequence}</span></td>
            <td>
                <span class="badge bg-${m.stability_class === 'STABLE' ? 'success' : (m.stability_class === 'VOLATILE' ? 'danger' : 'primary')}">
                    ${escapeHtml(m.stability_class)}
                </span>
            </td>
            <td>
                <span class="badge bg-${m.risk_level === 'HIGH_RISK' ? 'danger' : (m.risk_level === 'MEDIUM_RISK' ? 'warning text-dark' : 'success')}">
                    ${escapeHtml(m.risk_level)}
                </span>
            </td>
        </tr>
    `).join('');
}

async function synthesizeDecouplingPatch() {
    if (!currentGraphData || !currentGraphData.circular_cycles || currentGraphData.circular_cycles.length === 0) {
        // Synthesize sample decoupling for demonstration
        const sampleCycle = ['Atom\\Services\\OrderService', 'Atom\\Services\\InventoryService', 'Atom\\Services\\OrderService'];
        const strategy = document.getElementById('decoupleStrategySelect').value;
        const res = await apiFetch('/dependency/decouple', {
            method: 'POST',
            body: JSON.stringify({ cycle: sampleCycle, strategy: strategy })
        });
        if (res && res.success) {
            document.getElementById('decouplingPatchOutput').value = res.data.patch_code;
            if (typeof showToast === 'function') showToast(`Decoupling synthesized (${strategy})`, 'success');
        }
        return;
    }

    const cycle = currentGraphData.circular_cycles[0];
    const strategy = document.getElementById('decoupleStrategySelect').value;

    try {
        const res = await apiFetch('/dependency/decouple', {
            method: 'POST',
            body: JSON.stringify({ cycle: cycle, strategy: strategy })
        });

        if (res && res.success) {
            document.getElementById('decouplingPatchOutput').value = res.data.patch_code;
            if (typeof showToast === 'function') showToast(`Decoupling patch synthesized for ${res.data.target_classes.join(' & ')}`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Decoupling synthesis error: ' + e.message, 'error');
    }
}

function runCycleSimulationDemo() {
    simulatedCycleActive = !simulatedCycleActive;

    if (simulatedCycleActive) {
        const circularGraph = {
            'Atom\\Billing\\InvoiceService': ['Atom\\Payment\\PaymentGateway', 'Atom\\Customer\\AccountManager'],
            'Atom\\Payment\\PaymentGateway': ['Atom\\Notification\\EmailNotifier', 'Atom\\Billing\\InvoiceService'],
            'Atom\\Customer\\AccountManager': ['Atom\\Telemetry\\TelemetryManager'],
            'Atom\\Notification\\EmailNotifier': ['Atom\\Telemetry\\TelemetryManager']
        };
        loadDependencyGraph(circularGraph);
        if (typeof showToast === 'function') showToast('Injected circular cycle: InvoiceService ↔ PaymentGateway', 'warning');
    } else {
        loadDependencyGraph();
        if (typeof showToast === 'function') showToast('Restored nominal clean DAG graph', 'success');
    }
}

function exportMermaid() {
    if (!currentGraphData || !currentGraphData.mermaid_diagram) return;
    navigator.clipboard.writeText(currentGraphData.mermaid_diagram);
    if (typeof showToast === 'function') showToast('Mermaid.js diagram copied to clipboard!', 'info');
}

// ===== Canvas Force Network Visualizer =====
function drawNetworkCanvas(data) {
    const canvas = document.getElementById('dependencyNetworkCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const width = canvas.parentElement.clientWidth || 600;
    const height = 420;
    canvas.width = width;
    canvas.height = height;

    ctx.clearRect(0, 0, width, height);

    const nodeKeys = Object.keys(data.nodes || {});
    if (nodeKeys.length === 0) return;

    const positions = {};
    const centerX = width / 2;
    const centerY = height / 2;
    const radius = Math.min(width, height) * 0.38;

    nodeKeys.forEach((key, idx) => {
        const angle = (idx / nodeKeys.length) * 2 * Math.PI - Math.PI / 2;
        positions[key] = {
            x: centerX + radius * Math.cos(angle),
            y: centerY + radius * Math.sin(angle),
            name: key.split('\\').pop() || key
        };
    });

    // Draw Edges
    (data.edges || []).forEach(edge => {
        const p1 = positions[edge.source];
        const p2 = positions[edge.target];
        if (!p1 || !p2) return;

        ctx.beginPath();
        ctx.moveTo(p1.x, p1.y);
        ctx.lineTo(p2.x, p2.y);
        ctx.strokeStyle = '#475569';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        // Arrow head
        const angle = Math.atan2(p2.y - p1.y, p2.x - p1.x);
        const arrowDist = 20;
        const ax = p2.x - arrowDist * Math.cos(angle);
        const ay = p2.y - arrowDist * Math.sin(angle);
        ctx.beginPath();
        ctx.moveTo(ax, ay);
        ctx.lineTo(ax - 8 * Math.cos(angle - Math.PI / 6), ay - 8 * Math.sin(angle - Math.PI / 6));
        ctx.lineTo(ax - 8 * Math.cos(angle + Math.PI / 6), ay - 8 * Math.sin(angle + Math.PI / 6));
        ctx.fillStyle = '#94A3B8';
        ctx.fill();
    });

    // Draw Nodes
    nodeKeys.forEach(key => {
        const p = positions[key];
        const nodeInfo = data.nodes[key] || {};
        const isVolatile = nodeInfo.stability_class === 'VOLATILE';

        ctx.beginPath();
        ctx.arc(p.x, p.y, 16, 0, 2 * Math.PI);
        ctx.fillStyle = isVolatile ? '#EF4444' : '#38BDF8';
        ctx.shadowColor = isVolatile ? '#EF4444' : '#38BDF8';
        ctx.shadowBlur = 10;
        ctx.fill();
        ctx.shadowBlur = 0;

        ctx.strokeStyle = '#FFFFFF';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Label
        ctx.fillStyle = '#F8FAFC';
        ctx.font = 'bold 11px system-ui, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(p.name, p.x, p.y + 30);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    loadDependencyGraph();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
