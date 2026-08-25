<?php
// ATOM Web Admin — Phase 63: Autonomous AST Code Linter & PSR-12 Style Auto-Fixer
$pageTitle = "PSR-12 Code Linter (Phase 63)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #FBBF24;">Autonomous AST Code Linter &amp; PSR-12 Auto-Fixer</h2>
        <p class="text-muted small mb-0">Phase 63: Automated AST Violation Detection, Strict Types Injection &amp; 1-Click PSR-12 Code Standard Auto-Formatting</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-warning text-dark fw-bold" onclick="autoFixCode()">
            <i class="bi bi-magic me-1"></i> 1-Click Auto-Fix PSR-12
        </button>
    </div>
</div>

<!-- Linter Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMPLIANCE SCORE</div>
            <div class="fs-4 fw-bold text-warning" id="metricScore">40% (3 Violations)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STANDARD</div>
            <div class="fs-4 fw-bold text-info">PSR-12 Extended</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STRICT TYPES</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricStrict" style="color: #34D399;">Enforced</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">FIXES APPLIED</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricFixes">0 Fixes</div>
        </div>
    </div>
</div>

<!-- Before vs After Editor -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-warning"><i class="bi bi-file-earmark-code me-2"></i>Target PHP Source Code</span>
                <button class="btn btn-xs btn-outline-warning" onclick="scanCode()"><i class="bi bi-search me-1"></i>Scan Violations</button>
            </div>
            <div class="card-body">
                <textarea id="rawCodeArea" class="form-control bg-black text-white border-secondary small mb-3" rows="12" style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars("<?php

class SampleController {
    public function index(): string {
        return 'hello world';
    }
}
?>"); ?></textarea>

                <button class="btn btn-warning text-dark fw-bold w-100" onclick="autoFixCode()">
                    <i class="bi bi-magic me-1"></i> Auto-Fix to PSR-12 Standard
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-check-circle-fill me-2"></i>Formatted PSR-12 Output</span>
                <button class="btn btn-xs btn-outline-secondary" onclick="copyFixedCode()"><i class="bi bi-clipboard me-1"></i>Copy Fixed Code</button>
            </div>
            <div class="card-body">
                <textarea id="fixedCodeArea" class="form-control bg-black text-emerald-400 border-secondary small" rows="12" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>Click 'Auto-Fix' to generate PSR-12 compliant code...</textarea>
            </div>
        </div>
    </div>
</div>

<script>
async function scanCode() {
    const code = document.getElementById('rawCodeArea').value;
    try {
        const res = await apiFetch('/refactoring/linter/scan', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricScore').innerText = `${d.compliance_score}% (${d.violations_count} Violations)`;
            if (typeof showToast === 'function') showToast(`Found ${d.violations_count} PSR-12 style violations!`, d.violations_count > 0 ? 'warning' : 'success');
        }
    } catch (e) {
        console.error(e);
    }
}

async function autoFixCode() {
    const code = document.getElementById('rawCodeArea').value;
    try {
        const res = await apiFetch('/refactoring/linter/fix', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });
        if (res && res.success) {
            const d = res.data;
            document.getElementById('fixedCodeArea').value = d.fixed_code;
            document.getElementById('metricScore').innerText = `${d.compliance_score_after}% (Compliant)`;
            document.getElementById('metricFixes').innerText = `${d.fixes_applied} Fixes Applied`;
            if (typeof showToast === 'function') showToast(`Code reformatted: ${d.fixes_applied} fixes applied!`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Fix error: ' + e.message, 'error');
    }
}

function copyFixedCode() {
    navigator.clipboard.writeText(document.getElementById('fixedCodeArea').value);
    if (typeof showToast === 'function') showToast('Fixed code copied to clipboard!', 'info');
}

document.addEventListener('DOMContentLoaded', () => {
    scanCode();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
