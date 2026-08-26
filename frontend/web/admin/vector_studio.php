<?php
// ATOM Web Admin — Phase 96: Real-Time Vector Similarity Index & High-Dimensional HNSW Cosine Search Crossbar
$pageTitle = "Vector Similarity Index (Phase 96)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #818CF8;">Vector Similarity Index &amp; Semantic Search</h2>
        <p class="text-muted small mb-0">Phase 96: High-Dimensional Cosine, Euclidean &amp; Dot-Product Search, L2 Normalization &amp; Metadata Filter</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-primary text-white fw-bold" onclick="searchVectorsDemo()">
            <i class="bi bi-search me-1"></i> Query Vector Index
        </button>
    </div>
</div>

<!-- Vector Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL VECTORS INDEXED</div>
            <div class="fs-4 fw-bold text-indigo-400" id="metricTotalVecs" style="color: #818CF8;">3 EMBEDDINGS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DIMENSION</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricDim">8-DIMENSIONAL</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SEARCH METRICS</div>
            <div class="fs-4 fw-bold text-emerald-400">Cosine / L2 / Dot</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SEARCH LATENCY</div>
            <div class="fs-4 fw-bold text-pink-400">&lt; 0.1 ms Top-K</div>
        </div>
    </div>
</div>

<!-- Search Controls & Matches Table -->
<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-indigo-400"><i class="bi bi-compass me-2"></i>Query Vector Embedding</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">QUERY EMBEDDING (8-D FLOAT ARRAY)</label>
                    <textarea id="queryVectorInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="3">[0.14, 0.46, 0.86, 0.22, 0.90, 0.16, 0.66, 0.32]</textarea>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">DISTANCE METRIC</label>
                        <select id="metricSelect" class="form-select bg-black text-white border-secondary small">
                            <option value="cosine" selected>Cosine Similarity</option>
                            <option value="euclidean">Euclidean Distance</option>
                            <option value="dot_product">Dot Product</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">TOP-K MATCHES</label>
                        <input type="number" id="topKInput" class="form-control bg-black text-white border-secondary small" value="3" min="1" max="10">
                    </div>
                </div>

                <button class="btn btn-sm btn-primary text-white fw-bold w-100" onclick="searchVectorsDemo()">
                    <i class="bi bi-search me-1"></i> Search Top-K Neighbors
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-list-stars text-cyan-400 me-2"></i>Top-K Nearest Neighbor Matches</span>
                <span class="badge bg-secondary" id="matchesBadge">3 MATCHES</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Rank</th>
                                <th>Vector ID</th>
                                <th>Similarity Score</th>
                                <th>Category</th>
                            </tr>
                        </thead>
                        <tbody id="matchesTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Loading matches...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadStats() {
    try {
        const res = await apiFetch('/ai/vector/stats');
        if (res && res.success) {
            document.getElementById('metricTotalVecs').innerText = `${res.data.total_vectors} EMBEDDINGS`;
            document.getElementById('metricDim').innerText = `${res.data.dimension}-DIMENSIONAL`;
        }
    } catch (e) {
        console.error(e);
    }
}

async function searchVectorsDemo() {
    let vec = [];
    try {
        vec = JSON.parse(document.getElementById('queryVectorInput').value);
    } catch (e) {
        if (typeof showToast === 'function') showToast('Invalid vector array JSON', 'error');
        return;
    }

    const metric = document.getElementById('metricSelect').value;
    const topK = parseInt(document.getElementById('topKInput').value) || 3;

    try {
        const res = await apiFetch('/ai/vector/search', {
            method: 'POST',
            body: JSON.stringify({
                query_vector: vec,
                top_k: topK,
                metric: metric
            })
        });

        if (res && res.success) {
            const matches = res.data.matches || [];
            document.getElementById('matchesBadge').innerText = `${matches.length} MATCHES`;

            const tbody = document.getElementById('matchesTableBody');
            if (matches.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">No matches found.</td></tr>`;
                return;
            }

            tbody.innerHTML = matches.map((m, i) => `
                <tr>
                    <td class="fw-bold text-indigo-400">#${i + 1}</td>
                    <td class="fw-bold text-white font-monospace">${escapeHtml(m.vector_id)}</td>
                    <td>
                        <span class="badge ${metric === 'cosine' && m.score > 0.95 ? 'bg-success' : 'bg-primary'} font-monospace">
                            ${m.score}
                        </span>
                    </td>
                    <td><span class="text-cyan-400">${escapeHtml(m.metadata.category || 'N/A')}</span></td>
                </tr>
            `).join('');

            if (typeof showToast === 'function') {
                showToast(`Retrieved ${matches.length} nearest neighbors`, 'success');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Search error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    searchVectorsDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
