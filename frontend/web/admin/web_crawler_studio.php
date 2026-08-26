<?php
// ATOM Web Admin — Phase 103: Autonomous Dynamic Web Crawler & Recursive Link Extractor Studio
$pageTitle = "Autonomous Web Crawler & Recursive Link Extractor";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #22D3EE;">Autonomous Web Crawler &amp; Link Extractor</h2>
        <p class="text-muted small mb-0">Phase 103 — Recursive multi-hop DOM parsing, noise stripping, code block &amp; table extraction, and SSRF defense</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-dark fw-bold" style="background: linear-gradient(135deg, #22D3EE 0%, #06B6D4 100%); border: none;" onclick="quickDemoCrawl()">
            <i class="bi bi-lightning-charge me-1"></i> Demo Research Crawl
        </button>
    </div>
</div>

<!-- Crawler Status Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CRAWLER STATUS</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricStatus">OPERATIONAL</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MAX DEPTH LIMIT</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricDepth">3 HOPS (BFS)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MAX PAGES CAP</div>
            <div class="fs-4 fw-bold text-info" id="metricPages">20 PAGES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SSRF &amp; REDACTION</div>
            <div class="fs-4 fw-bold text-warning" id="metricSsrf">STRICT RFC 1918</div>
        </div>
    </div>
</div>

<!-- Crawl Dispatcher Controls -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-cyan-300"><i class="bi bi-globe me-2"></i>Multi-Hop Web Crawl Dispatcher</span>
        <span class="badge bg-cyan-950 text-cyan-300 border border-cyan-500/40">BFS RECURSION</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label text-muted text-xs fw-bold">TARGET SEED URL</label>
                <input type="text" id="seedUrlInput" class="form-control bg-black text-white border-secondary text-xs" placeholder="e.g. https://www.php.net/manual/en/language.oop5.php" value="https://devdocs.io/php/">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted text-xs fw-bold">MAX RECURSION DEPTH</label>
                <select id="maxDepthSelect" class="form-select bg-black text-white border-secondary text-xs">
                    <option value="1">Depth 1 (Single Page)</option>
                    <option value="2" selected>Depth 2 (Direct Links)</option>
                    <option value="3">Depth 3 (Multi-Hop Deep)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted text-xs fw-bold">MAX PAGES</label>
                <input type="number" id="maxPagesInput" class="form-control bg-black text-white border-secondary text-xs" value="6" min="1" max="20">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted text-xs fw-bold">SCOPE</label>
                <select id="sameDomainSelect" class="form-select bg-black text-white border-secondary text-xs">
                    <option value="1" selected>Same Domain Only</option>
                    <option value="0">Include Outbound</option>
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-xs text-muted">
                <i class="bi bi-shield-check text-success me-1"></i> Noise tags, scripts, and private loopback/RFC 1918 IPs are filtered automatically.
            </div>
            <button id="btnDispatchCrawl" class="btn btn-sm text-dark fw-bold px-4" style="background: #22D3EE;" onclick="dispatchCrawl()">
                <i class="bi bi-search me-1"></i> Dispatch Autonomous Crawl
            </button>
        </div>
    </div>
</div>

<!-- Crawl Results & Dossier Section -->
<div id="crawlResultsContainer" style="display:none;" class="space-y-4">
    <!-- Summary Header -->
    <div class="card bg-dark border-secondary text-white p-3 shadow">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="badge bg-cyan-900 text-cyan-200 border border-cyan-500/40 me-2" id="resPagesBadge">0 PAGES</span>
                <span class="fw-bold text-white fs-6" id="resSummaryTitle">Crawl Results</span>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-black border border-secondary text-muted" id="resWordsBadge">0 WORDS</span>
                <span class="badge bg-black border border-secondary text-emerald-400" id="resDurationBadge">0.00s</span>
            </div>
        </div>
    </div>

    <!-- Extracted Pages Tabbed / Accordion View -->
    <div class="card bg-dark border-secondary text-white shadow">
        <div class="card-header border-secondary">
            <span class="fw-bold text-emerald-400"><i class="bi bi-journal-code me-2"></i>Extracted Content, Code Blocks &amp; Link Graph</span>
        </div>
        <div class="card-body p-3">
            <div id="pagesListContainer" class="space-y-3">
                <!-- Injected via JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
async function dispatchCrawl() {
    const seedUrl = document.getElementById('seedUrlInput').value.trim();
    const maxDepth = parseInt(document.getElementById('maxDepthSelect').value) || 2;
    const maxPages = parseInt(document.getElementById('maxPagesInput').value) || 6;
    const sameDomain = document.getElementById('sameDomainSelect').value === '1';

    if (!seedUrl) {
        alert('Please enter a target seed URL.');
        return;
    }

    const btn = document.getElementById('btnDispatchCrawl');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Crawling & Parsing...';

    const resultsBox = document.getElementById('crawlResultsContainer');
    resultsBox.style.display = 'block';
    document.getElementById('pagesListContainer').innerHTML = '<div class="text-center py-5 text-muted"><span class="spinner-border text-cyan-400 mb-2"></span><div>Autonomous crawler traversing link hierarchy...</div></div>';

    try {
        const res = await apiFetch('/search/crawler/crawl', {
            method: 'POST',
            body: JSON.stringify({
                url: seedUrl,
                max_depth: maxDepth,
                max_pages: maxPages,
                same_domain_only: sameDomain
            })
        });

        if (!res.success || !res.data) {
            document.getElementById('pagesListContainer').innerHTML = `<div class="text-danger p-3">Crawl error: ${escapeHtml(res.message || 'Unknown error')}</div>`;
            return;
        }

        const d = res.data;
        document.getElementById('resPagesBadge').innerText = `${d.total_pages_crawled} PAGES CRAWLED`;
        document.getElementById('resSummaryTitle').innerText = d.dossier_summary || 'Crawl Completed';
        document.getElementById('resWordsBadge').innerText = `${d.total_word_count.toLocaleString()} WORDS`;
        document.getElementById('resDurationBadge').innerText = `${d.duration_sec}s`;

        const pages = d.pages || [];
        if (!pages.length) {
            document.getElementById('pagesListContainer').innerHTML = '<div class="text-muted text-center py-4">No content extracted from target.</div>';
            return;
        }

        document.getElementById('pagesListContainer').innerHTML = pages.map((p, idx) => {
            const isSuccess = p.status === 'success';
            const codeBlocks = p.code_blocks || [];
            const tables = p.tables || [];
            const links = p.outbound_links || [];

            return `
                <div class="p-3 rounded bg-black border border-secondary">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-cyan-950 text-cyan-300 border border-cyan-500/40 text-[10px] me-1">DEPTH ${p.depth}</span>
                            <span class="fw-bold text-white text-sm">${escapeHtml(p.title || p.url)}</span>
                            <div class="text-muted text-[11px] font-monospace truncate max-w-md">${escapeHtml(p.url)}</div>
                        </div>
                        <span class="badge ${isSuccess ? 'bg-success' : 'bg-danger'}">${p.status.toUpperCase()}</span>
                    </div>

                    ${p.meta_desc ? `<p class="text-xs text-info mb-2"><em>${escapeHtml(p.meta_desc)}</em></p>` : ''}
                    
                    <!-- Clean Text Snippet -->
                    <div class="text-xs text-gray-300 mb-2 p-2 rounded bg-[#0d1117] border border-secondary/40" style="max-height: 120px; overflow-y:auto;">
                        ${escapeHtml(p.clean_text ? p.clean_text.substring(0, 500) + '...' : (p.error || 'No text extracted'))}
                    </div>

                    <!-- Code Blocks extracted -->
                    ${codeBlocks.length ? `
                        <div class="mb-2">
                            <div class="text-[11px] text-warning fw-bold mb-1"><i class="bi bi-code-square me-1"></i>Extracted Code Blocks (${codeBlocks.length}):</div>
                            <pre class="p-2 rounded bg-[#080b0f] text-emerald-400 text-xs font-mono custom-scroll mb-0" style="max-height: 140px; overflow-y:auto;">${escapeHtml(codeBlocks[0])}</pre>
                        </div>
                    ` : ''}

                    <!-- Outbound Links -->
                    <div class="d-flex justify-content-between align-items-center text-[10px] text-muted border-t border-secondary/50 pt-2 mt-2">
                        <span><i class="bi bi-link-45deg text-cyan-400 me-1"></i>${links.length} Outbound Links Discovered</span>
                        <span><i class="bi bi-file-text me-1"></i>${p.word_count || 0} Words</span>
                    </div>
                </div>
            `;
        }).join('');

    } catch (e) {
        document.getElementById('pagesListContainer').innerHTML = `<div class="text-danger p-3">Crawl request failed: ${escapeHtml(e.message)}</div>`;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search me-1"></i> Dispatch Autonomous Crawl';
    }
}

function quickDemoCrawl() {
    document.getElementById('seedUrlInput').value = 'https://devdocs.io/php/';
    document.getElementById('maxDepthSelect').value = '2';
    document.getElementById('maxPagesInput').value = '4';
    dispatchCrawl();
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
