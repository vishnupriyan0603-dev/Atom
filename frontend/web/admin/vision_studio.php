<?php
// ATOM Web Admin — Phase 42: Neural Vision, Code OCR & Multi-Modal UI Studio
$pageTitle = "Vision & Neural UI Studio (Phase 42)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">Neural Vision, Code OCR &amp; Multi-Modal UI Studio</h2>
        <p class="text-muted small mb-0">Phase 42: Multi-Modal Code Extraction, Wireframe-to-UI Code Synthesizer, Dynamic Schema Generator &amp; Live Interactive Preview</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="runQuickVisionDemo()">
            <i class="bi bi-play-circle-fill me-1"></i> Quick Demo
        </button>
    </div>
</div>

<!-- Studio Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">OCR CONFIDENCE</div>
            <div class="fs-4 fw-bold text-success" id="metricConfidence">98.5% (High Precision)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SUPPORTED FRAMEWORKS</div>
            <div class="fs-4 fw-bold text-info">Bootstrap 5 • Tailwind • Flutter</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DETECTED LANGUAGE</div>
            <div class="fs-4 fw-bold text-warning" id="metricLang">PHP 8.3 / AST</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SCHEMA SYNTHESIS</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">SQL DDL + Mermaid.js</div>
        </div>
    </div>
</div>

<!-- Main Studio Tabs -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary p-2">
        <ul class="nav nav-pills card-header-pills" id="visionStudioTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active text-white fw-bold py-2 px-3" id="tabOcrBtn" data-bs-toggle="pill" data-bs-target="#tabOcr" type="button">
                    <i class="bi bi-file-earmark-code me-1 text-info"></i> 1. Code OCR Extraction
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-white fw-bold py-2 px-3" id="tabUiBtn" data-bs-toggle="pill" data-bs-target="#tabUi" type="button">
                    <i class="bi bi-layout-text-window-reverse me-1 text-warning"></i> 2. Mockup to UI Synthesizer
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-white fw-bold py-2 px-3" id="tabSchemaBtn" data-bs-toggle="pill" data-bs-target="#tabSchema" type="button">
                    <i class="bi bi-diagram-3 me-1 text-emerald-400"></i> 3. Diagram to Schema &amp; ERD
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content" id="visionStudioTabContent">
            
            <!-- TAB 1: Code OCR Extraction -->
            <div class="tab-pane fade show active" id="tabOcr" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h5 class="fw-bold text-info mb-3"><i class="bi bi-camera me-2"></i>Image / Code Input Source</h5>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">UPLOAD IMAGE / SCREENSHOT</label>
                            <input type="file" id="ocrFileInput" class="form-control bg-black text-white border-secondary" accept="image/*" onchange="handleOcrImageUpload(event)">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">TARGET LANGUAGE</label>
                            <select id="ocrLangSelect" class="form-select bg-black text-white border-secondary">
                                <option value="auto">Auto-Detect</option>
                                <option value="php">PHP 8.x</option>
                                <option value="javascript">JavaScript / TypeScript</option>
                                <option value="python">Python 3.x</option>
                                <option value="sql">SQL / DDL</option>
                                <option value="csharp">C# / .NET</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">RAW CODE / OCR SIMULATION INPUT</label>
                            <textarea id="ocrRawInput" class="form-control bg-black text-white border-secondary" rows="8" style="font-family: monospace; font-size: 13px;">class PaymentGatewayController extends BaseApiController {
    public function executeRefund(string $transactionId, float $amount): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Refund amount must be positive");
        }
        $result = $this->processor->refund($transactionId, $amount);
        return [ 'success' => true, 'refund_id' => $result->id ];
    }
}</textarea>
                        </div>

                        <button class="btn btn-info text-dark fw-bold w-100" onclick="triggerCodeOcr()">
                            <i class="bi bi-cpu me-1"></i> Extract &amp; Reconstruct Code
                        </button>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-success mb-0"><i class="bi bi-code-square me-2"></i>Reconstructed Code &amp; Symbols</h5>
                            <button class="btn btn-xs btn-outline-secondary" onclick="copyCode('ocrOutputCode')"><i class="bi bi-clipboard me-1"></i>Copy</button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">EXTRACTED AST SYMBOLS</label>
                            <div id="ocrSymbolsBadgeArea" class="d-flex gap-2 flex-wrap">
                                <span class="badge bg-primary">Class: PaymentGatewayController</span>
                                <span class="badge bg-secondary">Method: executeRefund()</span>
                            </div>
                        </div>

                        <textarea id="ocrOutputCode" class="form-control bg-black text-emerald-400 border-secondary" rows="12" style="font-family: monospace; font-size: 13px; color: #34D399;" readonly>// Normalized syntax and reconstructed code will appear here...</textarea>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Mockup to UI Synthesizer -->
            <div class="tab-pane fade" id="tabUi" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-5">
                        <h5 class="fw-bold text-warning mb-3"><i class="bi bi-palette me-2"></i>Design Spec &amp; Target Framework</h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">UI TITLE / MODULE NAME</label>
                            <input type="text" id="uiTitleInput" class="form-control bg-black text-white border-secondary" value="Analytics Telemetry & Security Hub">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">TARGET FRAMEWORK</label>
                                <select id="uiFrameworkSelect" class="form-select bg-black text-white border-secondary" onchange="triggerUiSynthesis()">
                                    <option value="bootstrap5" selected>Bootstrap 5 (Dark Mode)</option>
                                    <option value="tailwind">Tailwind CSS 3.x</option>
                                    <option value="vanilla">Vanilla HTML5 + CSS</option>
                                    <option value="flutter">Flutter Dart Widget</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small fw-bold">THEME PALETTE</label>
                                <select id="uiThemeSelect" class="form-select bg-black text-white border-secondary" onchange="triggerUiSynthesis()">
                                    <option value="dark" selected>Sleek Dark (#0F172A)</option>
                                    <option value="glass">Glassmorphism</option>
                                    <option value="light">Crisp Light</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">MOCKUP LAYOUT DESCRIPTION / WIREFRAME TAGS</label>
                            <textarea id="uiDescInput" class="form-control bg-black text-white border-secondary" rows="5" style="font-family: monospace; font-size: 13px;">Header navbar with title 'ATOM Gateway' and navigation links.
Metric card showing '99.98% High Availability'.
Configuration form with fields 'API Endpoint URL', 'Client Secret', and 'Rate Limit (req/min)' and button 'Deploy Cluster'.
Data grid table showing active worker processes and latencies.</textarea>
                        </div>

                        <button class="btn btn-warning text-dark fw-bold w-100 mb-3" onclick="triggerUiSynthesis()">
                            <i class="bi bi-magic me-1"></i> Synthesize Responsive Code
                        </button>
                    </div>

                    <div class="col-md-7">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-info mb-0"><i class="bi bi-display me-2"></i>Live Interactive Preview &amp; Code</h5>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-info active" id="btnViewPreview" onclick="toggleUiView('preview')">Live Preview</button>
                                <button class="btn btn-outline-info" id="btnViewCode" onclick="toggleUiView('code')">Source Code</button>
                                <button class="btn btn-outline-secondary" onclick="copyCode('uiGeneratedCodeArea')"><i class="bi bi-clipboard"></i></button>
                            </div>
                        </div>

                        <!-- Live Iframe Preview Area -->
                        <div id="uiPreviewContainer" class="border border-secondary rounded overflow-hidden" style="min-height: 380px; background: #0b0f19;">
                            <iframe id="uiLiveFrame" style="width: 100%; height: 380px; border: none;"></iframe>
                        </div>

                        <!-- Source Code Textarea -->
                        <div id="uiCodeContainer" class="d-none">
                            <textarea id="uiGeneratedCodeArea" class="form-control bg-black text-white border-secondary" rows="16" style="font-family: monospace; font-size: 12px;" readonly></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Diagram to Schema & ERD -->
            <div class="tab-pane fade" id="tabSchema" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-5">
                        <h5 class="fw-bold text-emerald-400 mb-3" style="color: #34D399;"><i class="bi bi-diagram-3 me-2"></i>Architecture Diagram &amp; ERD Spec</h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">SCHEMA TITLE</label>
                            <input type="text" id="schemaTitleInput" class="form-control bg-black text-white border-secondary" value="EcommerceOrdersArchitecture">
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">ENTITY &amp; RELATIONSHIP DESCRIPTION</label>
                            <textarea id="schemaDescInput" class="form-control bg-black text-white border-secondary" rows="8" style="font-family: monospace; font-size: 13px;">[Customers]
- id: integer
- full_name: varchar
- email: varchar
- loyalty_tier: varchar

[Orders]
- id: integer
- customer_id: integer
- total_amount: decimal
- status: varchar

[OrderItems]
- id: integer
- order_id: integer
- sku: varchar
- quantity: integer
- price_each: decimal</textarea>
                        </div>

                        <button class="btn btn-success fw-bold w-100" onclick="triggerSchemaSynthesis()">
                            <i class="bi bi-database-check me-1"></i> Generate SQL DDL &amp; Mermaid ERD
                        </button>
                    </div>

                    <div class="col-md-7">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-info mb-0"><i class="bi bi-filetype-sql me-2"></i>Generated SQL DDL Schema</h5>
                            <button class="btn btn-xs btn-outline-secondary" onclick="copyCode('schemaOutputSql')"><i class="bi bi-clipboard me-1"></i>Copy SQL</button>
                        </div>

                        <textarea id="schemaOutputSql" class="form-control bg-black text-info border-secondary mb-3" rows="9" style="font-family: monospace; font-size: 12px; color: #38BDF8;" readonly>-- SQL DDL schema will be generated here...</textarea>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-warning mb-0"><i class="bi bi-bezier2 me-1"></i>Mermaid.js Diagram Spec</h6>
                            <button class="btn btn-xs btn-outline-secondary" onclick="copyCode('schemaOutputMermaid')"><i class="bi bi-clipboard me-1"></i>Copy Mermaid</button>
                        </div>

                        <textarea id="schemaOutputMermaid" class="form-control bg-black text-warning border-secondary" rows="6" style="font-family: monospace; font-size: 12px; color: #FBBF24;" readonly>classDiagram
    %% Mermaid schema graph</textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
let currentUploadedImageBase64 = '';

function handleOcrImageUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        currentUploadedImageBase64 = e.target.result;
        if (typeof showToast === 'function') showToast(`Image loaded: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`, 'info');
        triggerCodeOcr();
    };
    reader.readAsDataURL(file);
}

async function triggerCodeOcr() {
    const raw = document.getElementById('ocrRawInput').value;
    const lang = document.getElementById('ocrLangSelect').value;

    try {
        const data = await apiFetch('/vision/ocr/code', {
            method: 'POST',
            body: JSON.stringify({
                text: raw,
                base64: currentUploadedImageBase64,
                language: lang,
                clean_indentation: true
            })
        });

        if (data && data.success) {
            document.getElementById('ocrOutputCode').value = data.data.code;
            document.getElementById('metricLang').innerText = data.data.language.toUpperCase();
            document.getElementById('metricConfidence').innerText = `${(data.data.confidence * 100).toFixed(1)}% (AST Verified)`;

            const symbolsArea = document.getElementById('ocrSymbolsBadgeArea');
            const syms = data.data.symbols || {};
            let badges = '';
            (syms.classes || []).forEach(c => badges += `<span class="badge bg-primary">Class: ${escapeHtml(c)}</span> `);
            (syms.functions || []).forEach(f => badges += `<span class="badge bg-secondary">Method: ${escapeHtml(f)}()</span> `);
            symbolsArea.innerHTML = badges || '<span class="text-muted small">No global symbols extracted</span>';
            if (typeof showToast === 'function') showToast('Code OCR extraction completed', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Error during OCR processing: ' + e.message, 'error');
    }
}

async function triggerUiSynthesis() {
    const title = document.getElementById('uiTitleInput').value;
    const framework = document.getElementById('uiFrameworkSelect').value;
    const theme = document.getElementById('uiThemeSelect').value;
    const desc = document.getElementById('uiDescInput').value;

    try {
        const data = await apiFetch('/vision/ui/synthesize', {
            method: 'POST',
            body: JSON.stringify({
                title: title,
                framework: framework,
                theme: theme,
                description: desc
            })
        });

        if (data && data.success) {
            const code = data.data.generated_code;
            document.getElementById('uiGeneratedCodeArea').value = code;

            // Render in Live Iframe Preview
            const iframe = document.getElementById('uiLiveFrame');
            const doc = iframe.contentWindow.document;
            doc.open();
            if (framework === 'bootstrap5') {
                doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"><style>body{background:#0F172A;color:#F8FAFC;}</style></head><body>${code}</body></html>`);
            } else {
                doc.write(code);
            }
            doc.close();
            if (typeof showToast === 'function') showToast(`UI synthesized in ${framework.toUpperCase()}`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('UI synthesis error: ' + e.message, 'error');
    }
}

async function triggerSchemaSynthesis() {
    const title = document.getElementById('schemaTitleInput').value;
    const desc = document.getElementById('schemaDescInput').value;

    try {
        const data = await apiFetch('/vision/diagram/schema', {
            method: 'POST',
            body: JSON.stringify({
                title: title,
                description: desc
            })
        });

        if (data && data.success) {
            document.getElementById('schemaOutputSql').value = data.data.sql_ddl;
            document.getElementById('schemaOutputMermaid').value = data.data.mermaid_diagram;
            if (typeof showToast === 'function') showToast('SQL Schema & Mermaid diagram generated', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Schema synthesis error: ' + e.message, 'error');
    }
}

function toggleUiView(view) {
    if (view === 'preview') {
        document.getElementById('uiPreviewContainer').classList.remove('d-none');
        document.getElementById('uiCodeContainer').classList.add('d-none');
        document.getElementById('btnViewPreview').classList.add('active');
        document.getElementById('btnViewCode').classList.remove('active');
    } else {
        document.getElementById('uiPreviewContainer').classList.add('d-none');
        document.getElementById('uiCodeContainer').classList.remove('d-none');
        document.getElementById('btnViewPreview').classList.remove('active');
        document.getElementById('btnViewCode').classList.add('active');
    }
}

function copyCode(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    navigator.clipboard.writeText(el.value);
    if (typeof showToast === 'function') showToast('Copied to clipboard!', 'info');
}

function runQuickVisionDemo() {
    triggerCodeOcr();
    triggerUiSynthesis();
    triggerSchemaSynthesis();
}

document.addEventListener('DOMContentLoaded', () => {
    runQuickVisionDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
