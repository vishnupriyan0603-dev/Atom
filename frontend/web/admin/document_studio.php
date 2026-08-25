<?php
// ATOM Web Admin — Phase 84: Real-Time Dynamic Markdown-to-PDF Streaming Renderer & Vector Asset Inliner
$pageTitle = "Markdown PDF Studio (Phase 84)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #0284C7;">Markdown-to-PDF Streaming Renderer</h2>
        <p class="text-muted small mb-0">Phase 84: Vector SVG Inliner, Print CSS Page Compositor, Multi-Page Pagination &amp; Zero-Leakage Document Compiler</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-primary text-white fw-bold" onclick="renderPdfDocument()">
            <i class="bi bi-file-earmark-pdf me-1"></i> Compile Document
        </button>
    </div>
</div>

<!-- Document Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PAGE ESTIMATE</div>
            <div class="fs-4 fw-bold text-sky-400" id="metricPages">1 PAGE (A4)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">WORD COUNT</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricWords" style="color: #34D399;">35 WORDS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">THEME PROFILE</div>
            <div class="fs-4 fw-bold text-indigo-400" id="metricTheme">CORPORATE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ASSET INLINER</div>
            <div class="fs-4 fw-bold text-info">Zero-Leakage (SVG)</div>
        </div>
    </div>
</div>

<!-- Split Pane Editor & Live Print Preview -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-markdown text-sky-400 me-2"></i>Markdown Editor</span>
                <div class="d-flex gap-2">
                    <button class="btn btn-xs btn-outline-light" onclick="loadTemplate('arch_rfc')">RFC</button>
                    <button class="btn btn-xs btn-outline-light" onclick="loadTemplate('security_audit')">Security Report</button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">DOCUMENT TITLE</label>
                    <input type="text" id="docTitleInput" class="form-control bg-black text-white border-secondary small" value="Architecture RFC: Distributed Event Mesh">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">MARKDOWN SOURCE</label>
                    <textarea id="markdownInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="10"># Architecture RFC: Distributed Event Mesh

## 1. Executive Summary
This document specifies the real-time event distribution fabric.

## 2. Key Architecture Metrics
- **Throughput:** 100,000 events/sec
- **Latency:** < 5ms P99 SLA
- **Encryption:** AES-256-GCM End-to-End

> Verified zero-eval compliance across all core subsystems.</textarea>
                </div>

                <button class="btn btn-sm btn-primary text-white fw-bold w-100" onclick="renderPdfDocument()">
                    <i class="bi bi-play-circle-fill me-1"></i> Render Print-Ready Preview
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-sky-400"><i class="bi bi-file-earmark-richtext me-2"></i>Print HTML Preview</span>
                <span class="badge bg-secondary" id="renderBadge">COMPILED</span>
            </div>
            <div class="card-body p-0">
                <iframe id="previewFrame" class="w-100 bg-white rounded-bottom" style="height: 400px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
async function renderPdfDocument() {
    const md = document.getElementById('markdownInput').value.trim();
    const title = document.getElementById('docTitleInput').value.trim();

    try {
        const res = await apiFetch('/documents/render/pdf', {
            method: 'POST',
            body: JSON.stringify({ markdown: md, title: title, theme: 'corporate' })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricPages').innerText = `${d.page_estimate} ${d.page_estimate > 1 ? 'PAGES' : 'PAGE'} (A4)`;
            document.getElementById('metricWords').innerText = `${d.word_count} WORDS`;
            document.getElementById('metricTheme').innerText = d.theme.toUpperCase();

            const iframe = document.getElementById('previewFrame');
            iframe.srcdoc = d.html;

            if (typeof showToast === 'function') showToast(`Rendered: ${d.title} (~${d.page_estimate} pages)`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Render error: ' + e.message, 'error');
    }
}

async function loadTemplate(id) {
    try {
        const res = await apiFetch('/documents/templates');
        if (res && res.success) {
            const t = (res.data || []).find(item => item.id === id);
            if (t) {
                document.getElementById('docTitleInput').value = t.name;
                document.getElementById('markdownInput').value = t.markdown;
                renderPdfDocument();
            }
        }
    } catch (e) {
        console.error(e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    renderPdfDocument();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
