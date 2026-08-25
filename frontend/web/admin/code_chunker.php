<?php
// ATOM Web Admin — Phase 76: Autonomous Semantic Code Chunk Splitter & AST Call-Tree Indexer
$pageTitle = "Semantic Code Chunker (Phase 76)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">Semantic Code Chunker &amp; AST Call-Tree Indexer</h2>
        <p class="text-muted small mb-0">Phase 76: AST-Aware Class/Method Boundary Splitting, Token Bounds Slicing &amp; Symbol Dependency Graph Extraction</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="processSemanticChunking()">
            <i class="bi bi-diagram-3-fill me-1"></i> Split AST Chunks
        </button>
    </div>
</div>

<!-- Chunker Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL AST CHUNKS</div>
            <div class="fs-4 fw-bold text-sky-400" id="metricChunksCount">2 CHUNKS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">INVOKED SYMBOLS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricSymbolsCount" style="color: #34D399;">3 SYMBOLS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">LINES PROCESSED</div>
            <div class="fs-4 fw-bold text-warning" id="metricLinesCount">18 LINES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">INDEXER ENGINE</div>
            <div class="fs-4 fw-bold text-info">AST-Aware</div>
        </div>
    </div>
</div>

<!-- Source Code Input & Chunks Hierarchy Table -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-sky-400"><i class="bi bi-code-square me-2"></i>Source Code Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ENTER CODE TO CHUNK</label>
                    <textarea id="codeInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="8"><?php echo htmlspecialchars("<?php\nclass AuthenticationEngine {\n    public function login(string \$user, string \$pass): bool {\n        \$clean = \$this->redactSecret(\$pass);\n        return SecurityManager::verifyHash(\$user, \$clean);\n    }\n\n    public function logout(): void {\n        \$this->clearSession();\n    }\n}"); ?></textarea>
                </div>

                <button class="btn btn-sm btn-info text-dark fw-bold w-100" onclick="processSemanticChunking()">
                    <i class="bi bi-scissors me-1"></i> Slice &amp; Index Symbols
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-node-plus-fill me-2"></i>Semantic AST Chunks</span>
                <span class="badge bg-secondary" id="chunksBadge">2 CHUNKS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Symbol</th>
                                <th>Type</th>
                                <th>Lines</th>
                                <th>Tokens</th>
                            </tr>
                        </thead>
                        <tbody id="chunksTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Loading chunks...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function processSemanticChunking() {
    const code = document.getElementById('codeInput').value;

    try {
        const splitRes = await apiFetch('/brain/chunker/split', {
            method: 'POST',
            body: JSON.stringify({ code: code, language: 'php' })
        });

        const treeRes = await apiFetch('/brain/chunker/call-tree', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });

        if (splitRes && splitRes.success) {
            const d = splitRes.data;
            document.getElementById('metricChunksCount').innerText = `${d.total_chunks} CHUNKS`;
            document.getElementById('metricLinesCount').innerText = `${d.total_lines} LINES`;
            document.getElementById('chunksBadge').innerText = `${d.total_chunks} CHUNKS`;

            renderChunksTable(d.chunks || []);
        }

        if (treeRes && treeRes.success) {
            document.getElementById('metricSymbolsCount').innerText = `${treeRes.data.distinct_calls_found} SYMBOLS`;
        }

        if (typeof showToast === 'function') showToast('Code split into semantic AST chunks', 'success');
    } catch (e) {
        if (typeof showToast === 'function') showToast('Chunking error: ' + e.message, 'error');
    }
}

function renderChunksTable(chunks) {
    const tbody = document.getElementById('chunksTableBody');
    if (!chunks || chunks.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">No chunks generated.</td></tr>`;
        return;
    }

    tbody.innerHTML = chunks.map(c => `
        <tr>
            <td class="fw-bold text-white"><i class="bi bi-box-seam text-sky-400 me-1"></i>${escapeHtml(c.symbol_name)}</td>
            <td><span class="badge bg-secondary">${escapeHtml(c.symbol_type)}</span></td>
            <td>${c.start_line} - ${c.end_line}</td>
            <td class="text-emerald-400">~${c.token_estimate} tok</td>
        </tr>
    `).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    processSemanticChunking();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
