<?php
// ATOM Web Admin — Phase 57: Autonomous OpenAPI 3.1 Schema & Multi-Language SDK Generator
$pageTitle = "OpenAPI & SDK Studio (Phase 57)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #6366F1;">Autonomous OpenAPI 3.1 Schema &amp; SDK Studio</h2>
        <p class="text-muted small mb-0">Phase 57: Automated REST Route Reflection, OpenAPI 3.1 Specification &amp; Multi-Language Client SDK Generator (TS, Python, C#, PHP)</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: #6366F1;" onclick="downloadOpenApiSpec()">
            <i class="bi bi-download me-1"></i> Download OpenAPI JSON
        </button>
    </div>
</div>

<!-- Specs & Languages Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SPECIFICATION VERSION</div>
            <div class="fs-4 fw-bold text-indigo-400">OpenAPI 3.1.0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SUPPORTED LANGUAGES</div>
            <div class="fs-4 fw-bold text-info">TS • Python • C# • PHP</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">API ENDPOINTS</div>
            <div class="fs-4 fw-bold text-success">57 SUBSYSTEMS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TYPE DEFINITIONS</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">100% Strongly Typed</div>
        </div>
    </div>
</div>

<!-- Main Section: SDK Generator -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2 align-items-center">
            <span class="fw-bold fs-6"><i class="bi bi-code-square me-2 text-indigo-400"></i>Generated Client SDK Wrapper</span>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-xs btn-outline-light active" id="btnTs" onclick="switchLanguage('typescript')">TypeScript</button>
                <button class="btn btn-xs btn-outline-light" id="btnPy" onclick="switchLanguage('python')">Python</button>
                <button class="btn btn-xs btn-outline-light" id="btnCs" onclick="switchLanguage('csharp')">C# .NET</button>
                <button class="btn btn-xs btn-outline-light" id="btnPhp" onclick="switchLanguage('php')">PHP</button>
            </div>
        </div>
        <button class="btn btn-xs btn-outline-secondary" onclick="copySdkCode()"><i class="bi bi-clipboard me-1"></i>Copy SDK</button>
    </div>
    <div class="card-body p-3">
        <textarea id="sdkCodeDisplay" class="form-control bg-black text-emerald-400 border-secondary small" rows="16" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>Loading SDK...</textarea>
    </div>
</div>

<script>
let currentLanguage = 'typescript';

async function switchLanguage(lang) {
    currentLanguage = lang;
    document.querySelectorAll('.btn-group button').forEach(b => b.classList.remove('active'));
    if (lang === 'typescript') document.getElementById('btnTs').classList.add('active');
    if (lang === 'python') document.getElementById('btnPy').classList.add('active');
    if (lang === 'csharp') document.getElementById('btnCs').classList.add('active');
    if (lang === 'php') document.getElementById('btnPhp').classList.add('active');

    try {
        const res = await apiFetch('/docs/generate-sdk', {
            method: 'POST',
            body: JSON.stringify({ language: lang })
        });
        if (res && res.success) {
            document.getElementById('sdkCodeDisplay').value = res.data.code;
        }
    } catch (e) {
        console.error(e);
    }
}

async function downloadOpenApiSpec() {
    try {
        const res = await apiFetch('/docs/openapi.json');
        if (res && res.success) {
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(res.data, null, 2));
            const downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", "atom-openapi-3.1.0.json");
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
            if (typeof showToast === 'function') showToast('OpenAPI 3.1.0 spec downloaded!', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Download error: ' + e.message, 'error');
    }
}

function copySdkCode() {
    navigator.clipboard.writeText(document.getElementById('sdkCodeDisplay').value);
    if (typeof showToast === 'function') showToast(`Copied ${currentLanguage.toUpperCase()} SDK to clipboard!`, 'info');
}

document.addEventListener('DOMContentLoaded', () => {
    switchLanguage('typescript');
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
