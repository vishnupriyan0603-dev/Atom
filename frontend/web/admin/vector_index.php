<?php
// ATOM Web Admin — Phase 44: Edge-Native HNSW Vector Index & Semantic Retrieval Hub
$pageTitle = "HNSW Vector Index & Semantic Space (Phase 44)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">HNSW Neural Vector Index &amp; Semantic Retrieval</h2>
        <p class="text-muted small mb-0">Phase 44: Pure PHP/SQLite-Native Hierarchical Navigable Small World (HNSW) Multi-Layer Vector Graph, O(log N) ANN Search &amp; Sub-millisecond Semantic Query Engine</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="loadHnswStats();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Stats
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="clearHnswIndex()">
            <i class="bi bi-trash me-1"></i> Clear Index
        </button>
    </div>
</div>

<!-- Vector Space Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">INDEXED VECTORS</div>
            <div class="fs-4 fw-bold text-info" id="metricTotalVectors">0 VECTORS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EMBEDDING DIMENSION ($D$)</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">64-D DENSE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HNSW MAX LAYER ($L$)</div>
            <div class="fs-4 fw-bold text-warning" id="metricMaxLayer">LAYER 0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SEARCH COMPLEXITY</div>
            <div class="fs-4 fw-bold text-cyan-400" style="color: #06B6D4;">$O(\log N)$ ANN</div>
        </div>
    </div>
</div>

<!-- Main Section: Query Sandbox & Document Ingestion -->
<div class="row g-4 mb-4">
    
    <!-- 1. Live Semantic Vector Search Console -->
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-search me-2"></i>Live Approximate Nearest Neighbor (ANN) Query</span>
                <span class="badge bg-secondary">BEAM ef=32</span>
            </div>
            <div class="card-body">
                <div class="input-group mb-3">
                    <input type="text" id="hnswQueryInput" class="form-control bg-black text-white border-secondary" placeholder="Search semantic space (e.g. 'Tamil voice synthesis', 'Zero knowledge crypto')..." value="Tamil voice synthesis with audio DSP">
                    <button class="btn btn-info text-dark fw-bold px-4" onclick="executeVectorSearch()">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Search
                    </button>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label text-muted small fw-bold mb-0">MATCHED NEAREST NEIGHBORS (TOP-K)</label>
                    <span class="text-xs text-muted" id="searchLatencyBadge">Latency: &lt;1.2ms</span>
                </div>

                <div id="vectorSearchResultsContainer" class="space-y-2" style="min-height: 280px;">
                    <div class="text-center text-muted py-5">Enter query and click Search to query HNSW vector graph</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Fast Document / Vector Ingestion -->
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-emerald-400" style="color: #34D399;"><i class="bi bi-plus-circle-fill me-2"></i>Ingest &amp; Embed Knowledge Chunk</span>
                <span class="badge bg-success">HNSW AUTO-ROUTED</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DOCUMENT / CHUNK ID</label>
                    <input type="text" id="ingestDocId" class="form-control bg-black text-white border-secondary" value="custom_knowledge_doc_1">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">RAW TEXT CONTENT OR CODE SNIPPET</label>
                    <textarea id="ingestDocContent" class="form-control bg-black text-white border-secondary" rows="5" placeholder="Enter text to generate dense 64-d embedding vector...">Autonomous edge intelligence and distributed peer-to-peer WebRTC signaling mesh with resilient gossip protocols.</textarea>
                </div>

                <button class="btn btn-success fw-bold w-100" onclick="ingestDocumentVector()">
                    <i class="bi bi-cpu-fill me-1"></i> Embed &amp; Insert into Graph
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 3. HNSW Hierarchical Layer Topology & Distribution -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary">
        <span class="fw-bold fs-6"><i class="bi bi-layers-fill me-2 text-warning"></i>HNSW Multi-Layer Node Distribution &amp; Small World Routing Depth</span>
    </div>
    <div class="card-body">
        <div id="hnswLayerDistributionContainer" class="row g-3">
            <!-- Layers dynamically populated -->
        </div>
    </div>
</div>

<script>
async function loadHnswStats() {
    try {
        const res = await apiFetch('/vector/index/stats');
        if (res && res.success) {
            const stats = res.data;
            document.getElementById('metricTotalVectors').innerText = `${stats.total_vectors} VECTORS`;
            document.getElementById('metricMaxLayer').innerText = `LAYER ${stats.max_layer}`;

            renderLayerDistribution(stats.layer_node_distribution || {});
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Failed to load HNSW stats: ' + e.message, 'error');
    }
}

function renderLayerDistribution(dist) {
    const container = document.getElementById('hnswLayerDistributionContainer');
    const layers = Object.entries(dist);

    if (layers.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted">Index is empty</div>';
        return;
    }

    container.innerHTML = layers.map(([layer, count]) => `
        <div class="col-md-3">
            <div class="p-3 bg-black border border-secondary rounded">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-info">Layer ${layer} (Level ${layer})</span>
                    <span class="badge bg-primary">${count} nodes</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-info" style="width: ${Math.min(100, Math.max(15, count * 15))}%;"></div>
                </div>
            </div>
        </div>
    `).join('');
}

async function executeVectorSearch() {
    const q = document.getElementById('hnswQueryInput').value;
    if (!q) return;

    const t0 = performance.now();
    try {
        const res = await apiFetch('/vector/search', {
            method: 'POST',
            body: JSON.stringify({ query: q, top_k: 5 })
        });

        const elapsed = (performance.now() - t0).toFixed(2);
        document.getElementById('searchLatencyBadge').innerText = `Latency: ${elapsed}ms`;

        if (res && res.success) {
            const results = res.data.results || [];
            const container = document.getElementById('vectorSearchResultsContainer');

            if (results.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4">No matching vectors found</div>';
                return;
            }

            container.innerHTML = results.map(r => `
                <div class="card bg-black border border-secondary p-3 mb-2 rounded">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-white"><i class="bi bi-file-earmark-text me-2 text-info"></i>${escapeHtml(r.id)}</span>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge bg-success">SIM: ${(r.similarity * 100).toFixed(1)}%</span>
                            <span class="badge bg-secondary text-xs">Dist: ${r.distance}</span>
                        </div>
                    </div>
                    <div class="text-muted small mt-1">Metadata: ${escapeHtml(JSON.stringify(r.metadata))}</div>
                </div>
            `).join('');

            if (typeof showToast === 'function') showToast(`Found ${results.length} nearest neighbors in ${elapsed}ms`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Vector search failed: ' + e.message, 'error');
    }
}

async function ingestDocumentVector() {
    const id = document.getElementById('ingestDocId').value;
    const content = document.getElementById('ingestDocContent').value;

    try {
        const res = await apiFetch('/vector/index/insert', {
            method: 'POST',
            body: JSON.stringify({ id: id, content: content })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Vector inserted for '${id}' (Total: ${res.data.total_indexed})`, 'success');
            loadHnswStats();
            executeVectorSearch();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Ingestion error: ' + e.message, 'error');
    }
}

async function clearHnswIndex() {
    if (!confirm('Are you sure you want to clear the HNSW Vector Index?')) return;
    try {
        const res = await apiFetch('/vector/index/clear', { method: 'DELETE' });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast('Vector index cleared', 'info');
            loadHnswStats();
            document.getElementById('vectorSearchResultsContainer').innerHTML = '<div class="text-center text-muted py-5">Index cleared</div>';
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Clear error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadHnswStats();
    executeVectorSearch();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
