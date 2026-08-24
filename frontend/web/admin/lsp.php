<?php
// ATOM Web Admin — Phase 26: Developer IDE Protocol & LSP Engine Dashboard
$pageTitle = "IDE Protocol & Language Server (LSP)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #6366F1;">Developer IDE Protocol &amp; LSP Engine</h2>
        <p class="text-muted small mb-0">JSON-RPC 2.0 Language Server Protocol v3.17 — AI Autocomplete, Hover Tooltips, and AST Refactoring</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%); border: none; color: white;" onclick="testLspCapabilities()">
            <i class="bi bi-plug me-1"></i> Check Capabilities
        </button>
    </div>
</div>

<!-- LSP Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PROTOCOL SPEC</div>
            <div class="fs-4 fw-bold" style="color:#6366F1;">JSON-RPC 2.0 (LSP 3.17)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMPLETION ENGINE</div>
            <div class="fs-4 fw-bold text-success">ACTIVE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HOVER TOOLTIPS</div>
            <div class="fs-4 fw-bold text-info">MARKDOWN READY</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AST REFACTORING</div>
            <div class="fs-4 fw-bold text-warning">4 ACTIONS</div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Panel 1: Interactive Code Editor & Autocomplete -->
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#6366F1;"><i class="bi bi-code-slash me-2"></i>Interactive Code Editor &amp; Autocomplete</span>
                <span class="badge bg-primary">LIVE TESTBENCH</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Source Code Buffer</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="lspCodeInput" rows="7" style="font-family: monospace; font-size: 13px;" onkeyup="triggerLiveCompletion(event)">
<?php
namespace App\Controllers;

class UserController extends BaseController
{
    public function getUser($id)
    {
        $db = \Config\Database::connect();
        return $this->respondSuccess(['id' => $id]);
    }
}
</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-info btn-sm flex-fill" onclick="requestCompletions()">
                        <i class="bi bi-lightning-fill me-1"></i> Trigger Completion
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="clearEditor()">
                        <i class="bi bi-trash me-1"></i> Clear
                    </button>
                </div>
                <div class="mt-3">
                    <label class="form-label text-muted small fw-bold">Completion Candidates</label>
                    <div class="p-2 bg-black border border-secondary rounded" id="completionList" style="max-height: 140px; overflow-y: auto; font-family: monospace; font-size: 12px;">
                        (Type in the editor or click Trigger Completion)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 2: Symbol Hover & Documentation Inspector -->
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#38BDF8;"><i class="bi bi-info-circle me-2"></i>Symbol Hover &amp; Type Hints</span>
                <span class="badge bg-info text-dark">HOVER</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Lookup Symbol</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-dark text-white border-secondary" id="hoverSymbolInput" value="AtomBrain" placeholder="e.g. AtomBrain, VisionEngine">
                        <button class="btn btn-outline-info" onclick="inspectSymbolHover()"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <label class="form-label text-muted small fw-bold">Hover Tooltip Markdown</label>
                <div class="p-3 bg-black border border-secondary rounded" id="hoverTooltipArea" style="min-height: 200px; color: #E2E8F0; font-family: monospace; white-space: pre-wrap; font-size: 12px;">
Hover over a symbol or click search to view documentation.
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 3: AST Refactoring Toolbox -->
    <div class="col-12">
        <div class="card bg-dark border-secondary text-white">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#F59E0B;"><i class="bi bi-wrench-adjustable me-2"></i>AST Code Refactoring Toolbox</span>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-warning btn-sm" onclick="applyRefactorAction('add_phpdoc')">
                        <i class="bi bi-chat-left-text me-1"></i> Add PHPDoc
                    </button>
                    <button class="btn btn-outline-success btn-sm" onclick="applyRefactorAction('add_type_hints')">
                        <i class="bi bi-tag me-1"></i> Add Type Hints
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="applyRefactorAction('format_syntax')">
                        <i class="bi bi-justify me-1"></i> Format Syntax
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Original Code</label>
                        <div class="p-2 bg-black border border-secondary rounded" id="refactorOriginal" style="min-height: 160px; font-family: monospace; font-size: 12px; color:#94A3B8; white-space: pre-wrap;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Transformed Code (AST Output)</label>
                        <div class="p-2 bg-black border border-secondary rounded" id="refactorOutput" style="min-height: 160px; font-family: monospace; font-size: 12px; color:#34D399; white-space: pre-wrap;">
Click a refactoring action button to apply AST transformation.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const API_BASE = window.ATOM_API_BASE || '/api';
const TOKEN    = localStorage.getItem('atom_token') || '';

function apiFetch(path, opts = {}) {
    return fetch(API_BASE + path, {
        ...opts,
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + TOKEN, ...(opts.headers || {}) }
    }).then(r => r.json());
}

function requestCompletions() {
    const code = document.getElementById('lspCodeInput').value;
    const lines = code.split('\n');
    const lastLine = lines[lines.length - 1] || '';

    apiFetch('/lsp/complete', {
        method: 'POST',
        body: JSON.stringify({ prefix: lastLine, file_name: 'UserController.php' })
    }).then(res => {
        if (!res.success) return;
        const list = document.getElementById('completionList');
        const items = res.data.items || [];
        if (items.length === 0) {
            list.innerHTML = '<span class="text-muted">No completions found.</span>';
            return;
        }

        list.innerHTML = items.map(it => `
            <div class="p-1 border-bottom border-secondary d-flex justify-content-between align-items-center">
                <span><code>${it.label}</code></span>
                <span class="text-muted small">${it.detail || ''}</span>
            </div>
        `).join('');
    });
}

function triggerLiveCompletion(e) {
    if (e.key === '.' || e.key === ':' || e.key === '>' || e.key === '$' || e.key === '\\') {
        requestCompletions();
    }
}

function inspectSymbolHover() {
    const sym = document.getElementById('hoverSymbolInput').value.trim() || 'AtomBrain';
    apiFetch('/lsp/hover', {
        method: 'POST',
        body: JSON.stringify({ symbol: sym })
    }).then(res => {
        if (res.success) {
            document.getElementById('hoverTooltipArea').textContent = res.data.contents?.value || JSON.stringify(res.data, null, 2);
        }
    });
}

function applyRefactorAction(action) {
    const code = document.getElementById('lspCodeInput').value;
    document.getElementById('refactorOriginal').textContent = code;
    document.getElementById('refactorOutput').textContent = 'Applying ' + action + '...';

    apiFetch('/lsp/refactor', {
        method: 'POST',
        body: JSON.stringify({ code: code, action: action })
    }).then(res => {
        if (res.success) {
            document.getElementById('refactorOutput').textContent = res.data.transformed_code;
        } else {
            document.getElementById('refactorOutput').textContent = 'Refactor failed: ' + (res.error || 'Error');
        }
    });
}

function testLspCapabilities() {
    apiFetch('/lsp/capabilities').then(res => {
        if (res.success) {
            alert('LSP Server Ready: ' + JSON.stringify(res.data.capabilities));
        }
    });
}

function clearEditor() {
    document.getElementById('lspCodeInput').value = '';
    document.getElementById('completionList').innerHTML = '(Cleared)';
}

document.addEventListener('DOMContentLoaded', function () {
    inspectSymbolHover();
    requestCompletions();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
