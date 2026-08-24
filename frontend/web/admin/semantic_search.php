<?php
// ATOM Web Admin — Phase 39: Autonomous Semantic Code Search & Vector Embedding Dashboard
$pageTitle = "Semantic Code Search";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #6366F1;">Semantic Code Search &amp; Vector Embeddings</h2>
        <p class="text-muted small mb-0">AST code chunking, multi-dimensional feature vector embeddings, cosine similarity search &amp; hybrid Reciprocal Rank Fusion (RRF)</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%); border: none;" onclick="runSearchDemo()">
            <i class="bi bi-search me-1"></i> Run Semantic Query
        </button>
    </div>
</div>

<!-- Search Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EMBEDDING SPACE</div>
            <div class="fs-4 fw-bold" style="color: #818CF8;" id="metricDim">64-D (L2 Unit Norm)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SIMILARITY METRIC</div>
            <div class="fs-4 fw-bold text-success" id="metricSim">Cosine Similarity</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">RANK FUSION</div>
            <div class="fs-4 fw-bold text-info" id="metricRRF">Hybrid RRF (k=60)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">INDEXED REPO SYMBOLS</div>
            <div class="fs-4 fw-bold text-warning" id="metricSymbols">Ready (In-Memory)</div>
        </div>
    </div>
</div>

<!-- Interactive Search & Indexing Grid -->
<div class="row g-4 mb-4">
    <!-- 1. Semantic Natural Language Search -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#818CF8;"><i class="bi bi-search me-2"></i>Natural Language Code Query</span>
                <span class="badge bg-primary text-white">VECTOR COSINE</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ENTER NATURAL LANGUAGE SEARCH INTENT</label>
                    <input type="text" id="queryInput" class="form-control bg-black text-white border-secondary" value="how to encrypt secret vault data with password">
                </div>
                <button class="btn btn-sm text-white fw-bold w-100 mb-3" style="background: #6366F1;" onclick="performSearch()">
                    <i class="bi bi-cpu me-1"></i> Search Repository Semantics (Top-3)
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 140px;">
                    <div class="text-muted small fw-bold mb-1">SEMANTIC SEARCH MATCHES:</div>
                    <div id="searchOutput" class="small text-indigo-300" style="font-family: monospace; white-space: pre-wrap; color: #A5B4FC;">
Type a query and click 'Search Repository Semantics'.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Code Chunking & Ingestion Console -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-file-earmark-code me-2"></i>AST Code Chunk &amp; Embedder</span>
                <span class="badge bg-info text-dark">AUTO-SEGMENTATION</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SOURCE CODE TO INGEST</label>
                    <textarea id="codeInput" class="form-control bg-black text-white border-secondary" rows="3" style="font-family: monospace;">class AuthService {
    public function verifyMfaToken(string $user, string $totp): bool {
        return true;
    }
}</textarea>
                </div>
                <button class="btn btn-sm btn-info text-dark fw-bold w-100 mb-3" onclick="indexCodeSnippet()">
                    <i class="bi bi-plus-circle me-1"></i> Segment, Embed &amp; Index Code
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 80px;">
                    <div class="text-muted small fw-bold mb-1">INGESTION STATUS:</div>
                    <div id="ingestOutput" class="small text-info" style="font-family: monospace; white-space: pre-wrap;">
Ready to ingest code snippets.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function performSearch() {
    const query = document.getElementById('queryInput').value;
    try {
        const res = await fetch('/api/v1/search/query', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ query: query, top_k: 3 })
        });
        const data = await res.json();
        if (data.success) {
            const list = data.data.results;
            if (list.length === 0) {
                document.getElementById('searchOutput').innerText = 'No matches found.';
                return;
            }
            document.getElementById('searchOutput').innerText = list.map((item, idx) => 
                `[Match #${idx + 1}] Similarity Score: ${(item.score * 100).toFixed(1)}%\n` +
                `  File   : ${item.metadata.file || 'Dynamic'}\n` +
                `  Symbol : ${item.id}\n` +
                `  Snippet: ${item.metadata.code || ''}`
            ).join('\n\n');
        }
    } catch (e) {
        document.getElementById('searchOutput').innerText = 'Error: ' + e.message;
    }
}

async function indexCodeSnippet() {
    const code = document.getElementById('codeInput').value;
    try {
        const res = await fetch('/api/v1/search/index', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ code: code, file: 'src/Auth/AuthService.php' })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('ingestOutput').innerText = 
                `✔ Ingestion Successful!\n` +
                `  Indexed Chunks : ${data.data.indexed_chunks}\n` +
                `  Total in Index : ${data.data.total_in_index}`;
        }
    } catch (e) {
        document.getElementById('ingestOutput').innerText = 'Error: ' + e.message;
    }
}

function runSearchDemo() {
    performSearch();
}

document.addEventListener('DOMContentLoaded', () => performSearch());
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
