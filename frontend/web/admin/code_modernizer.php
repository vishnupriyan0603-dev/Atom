<?php
// ATOM Web Admin — Phase 47: Autonomous AST Code Modernizer & OWASP Security Auto-Patcher
$pageTitle = "Code Modernizer & Security Auto-Patcher (Phase 47)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #10B981;">Autonomous AST Code Modernizer &amp; OWASP Security Auto-Patcher</h2>
        <p class="text-muted small mb-0">Phase 47: AST-Based PHP 8.3 Syntax Upgrades (Match Expressions, Nullsafe Operators, String Functions) &amp; Automated OWASP Vulnerability Patch Synthesizer (SQLi, XSS, Path Traversal)</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-success fw-bold" onclick="runModernizerDemo()">
            <i class="bi bi-magic me-1"></i> Run Modernizer Demo
        </button>
    </div>
</div>

<!-- Modernizer Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TARGET ENGINE RUNTIME</div>
            <div class="fs-4 fw-bold text-success">PHP 8.3 (Native)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TRANSFORMATIONS APPLIED</div>
            <div class="fs-4 fw-bold text-info" id="metricTransCount">0 RULES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DETECTED VULNERABILITIES</div>
            <div class="fs-4 fw-bold text-danger" id="metricVulnCount">0 THREATS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SECURITY COVERAGE</div>
            <div class="fs-4 fw-bold text-warning">OWASP Top 10 (CWE)</div>
        </div>
    </div>
</div>

<!-- Main Tabs -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary p-2">
        <ul class="nav nav-pills card-header-pills" id="modernizerTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active text-white fw-bold py-2 px-3" id="tabUpgradeBtn" data-bs-toggle="pill" data-bs-target="#tabUpgrade" type="button">
                    <i class="bi bi-arrow-up-circle me-1 text-info"></i> 1. PHP 8.3 Syntax Modernizer
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-white fw-bold py-2 px-3" id="tabSecurityBtn" data-bs-toggle="pill" data-bs-target="#tabSecurity" type="button">
                    <i class="bi bi-shield-shaded me-1 text-danger"></i> 2. OWASP Vulnerability Auto-Patcher
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content" id="modernizerTabContent">
            
            <!-- TAB 1: Syntax Modernizer -->
            <div class="tab-pane fade show active" id="tabUpgrade" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-warning mb-0"><i class="bi bi-code-slash me-2"></i>Legacy PHP Source Code</h5>
                            <button class="btn btn-xs btn-outline-secondary" onclick="loadSampleLegacyCode()">Load Legacy Sample</button>
                        </div>

                        <textarea id="legacyCodeInput" class="form-control bg-black text-white border-secondary small mb-3" rows="12" style="font-family: monospace; font-size: 12px;"><?php
function processAction($action, $user) {
    if (strpos($action, 'deploy') !== false) {
        $profile = $user !== null ? $user->getProfile() : null;
    }
    switch ($action) {
        case 'start':
            return 'STARTED';
        case 'pause':
            return 'PAUSED';
        default:
            return 'UNKNOWN';
    }
}
?></textarea>

                        <button class="btn btn-info text-dark fw-bold w-100" onclick="triggerCodeUpgrade()">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Upgrade Syntax to PHP 8.3
                        </button>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-emerald-400 mb-0" style="color: #34D399;"><i class="bi bi-check2-circle me-2"></i>Modernized PHP 8.3 Output</h5>
                            <button class="btn btn-xs btn-outline-secondary" onclick="copyModernizedCode()"><i class="bi bi-clipboard me-1"></i>Copy</button>
                        </div>

                        <div id="appliedTransformationsBox" class="p-2 mb-3 bg-black border border-secondary rounded text-xs text-info" style="min-height: 40px;">
                            Transformations will appear here...
                        </div>

                        <textarea id="modernizedCodeOutput" class="form-control bg-black text-emerald-400 border-secondary small" rows="12" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>// Modernized PHP 8.3 AST output will appear here...</textarea>
                    </div>
                </div>
            </div>

            <!-- TAB 2: OWASP Vulnerability Auto-Patcher -->
            <div class="tab-pane fade" id="tabSecurity" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-danger mb-0"><i class="bi bi-bug me-2"></i>Vulnerable Code Input</h5>
                            <button class="btn btn-xs btn-outline-secondary" onclick="loadVulnerableSample()">Load Exploit Sample</button>
                        </div>

                        <textarea id="vulnerableCodeInput" class="form-control bg-black text-danger border-secondary small mb-3" rows="12" style="font-family: monospace; font-size: 12px;"><?php
class UserController {
    public function search() {
        $id = $_GET['id'];
        $user = $this->db->query("SELECT * FROM users WHERE id = " . $id);
        echo $_GET['search_query'];
        $file = file_get_contents($_GET['profile_pic']);
        return $user;
    }
}
?></textarea>

                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-danger fw-bold flex-grow-1" onclick="scanSecurityVulnerabilities()">
                                <i class="bi bi-search me-1"></i> Scan Vulnerabilities
                            </button>
                            <button class="btn btn-danger fw-bold flex-grow-1" onclick="autoPatchSecurityVulnerabilities()">
                                <i class="bi bi-shield-fill-check me-1"></i> Auto-Patch Code (AST Fix)
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-success mb-0"><i class="bi bi-shield-check me-2"></i>Remediated / Hardened Code</h5>
                            <button class="btn btn-xs btn-outline-secondary" onclick="copyPatchedCode()"><i class="bi bi-clipboard me-1"></i>Copy Fix</button>
                        </div>

                        <div id="vulnerabilityFindingsBox" class="p-2 mb-3 bg-black border border-secondary rounded text-xs space-y-1" style="min-height: 40px;">
                            Click 'Scan Vulnerabilities' to inspect AST threat signatures.
                        </div>

                        <textarea id="patchedCodeOutput" class="form-control bg-black text-success border-secondary small" rows="12" style="font-family: monospace; font-size: 12px;" readonly>// Hardened, secure code with parameterized queries and XSS escaping will appear here...</textarea>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
async function triggerCodeUpgrade() {
    const code = document.getElementById('legacyCodeInput').value;
    try {
        const res = await apiFetch('/modernizer/upgrade', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });

        if (res && res.success) {
            document.getElementById('modernizedCodeOutput').value = res.data.modernized_code;
            document.getElementById('metricTransCount').innerText = `${res.data.transformation_count} RULES`;

            const box = document.getElementById('appliedTransformationsBox');
            if (res.data.transformations.length > 0) {
                box.innerHTML = res.data.transformations.map(t => `<div>✨ ${escapeHtml(t)}</div>`).join('');
            } else {
                box.innerHTML = '<div>Code is already modern.</div>';
            }

            if (typeof showToast === 'function') showToast('Code successfully upgraded to PHP 8.3!', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Upgrade error: ' + e.message, 'error');
    }
}

async function scanSecurityVulnerabilities() {
    const code = document.getElementById('vulnerableCodeInput').value;
    try {
        const res = await apiFetch('/modernizer/scan-security', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });

        if (res && res.success) {
            const vulns = res.data.vulnerabilities || [];
            document.getElementById('metricVulnCount').innerText = `${vulns.length} THREATS`;

            const box = document.getElementById('vulnerabilityFindingsBox');
            if (vulns.length > 0) {
                box.innerHTML = vulns.map(v => `
                    <div class="p-1.5 rounded bg-black border border-danger mb-1">
                        <span class="badge bg-danger">${v.severity}</span>
                        <span class="fw-bold text-white ms-1">[${v.cwe}] ${escapeHtml(v.title)}</span>
                    </div>
                `).join('');
            } else {
                box.innerHTML = '<div class="text-success fw-bold">✅ No security vulnerabilities detected.</div>';
            }

            if (typeof showToast === 'function') showToast(`Security scan complete: ${vulns.length} vulnerabilities found`, 'warning');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Scan error: ' + e.message, 'error');
    }
}

async function autoPatchSecurityVulnerabilities() {
    const code = document.getElementById('vulnerableCodeInput').value;
    try {
        const res = await apiFetch('/modernizer/auto-patch', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });

        if (res && res.success) {
            document.getElementById('patchedCodeOutput').value = res.data.patched_code;
            document.getElementById('metricVulnCount').innerText = `${res.data.remaining_vulnerabilities} THREATS`;

            const box = document.getElementById('vulnerabilityFindingsBox');
            box.innerHTML = res.data.patches_applied.map(p => `
                <div class="p-1.5 rounded bg-black border border-success mb-1 text-success">
                    <i class="bi bi-shield-check me-1"></i> ${escapeHtml(p)}
                </div>
            `).join('');

            if (typeof showToast === 'function') showToast(`Successfully patched ${res.data.patches_applied_count} vulnerabilities!`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Auto-patch error: ' + e.message, 'error');
    }
}

function loadSampleLegacyCode() {
    document.getElementById('legacyCodeInput').value = `<?php
function evaluateStatus($status, $account) {
    if (strpos($status, 'active') !== false) {
        $tier = $account !== null ? $account->getTier() : null;
    }
    switch ($status) {
        case 'pending':
            return 'STATUS_PENDING';
        case 'verified':
            return 'STATUS_VERIFIED';
        default:
            return 'STATUS_UNKNOWN';
    }
}
?>`;
    triggerCodeUpgrade();
}

function loadVulnerableSample() {
    document.getElementById('vulnerableCodeInput').value = `<?php
class AccountController {
    public function showProfile() {
        $uid = $_GET['user_id'];
        $user = $this->db->query("SELECT * FROM users WHERE id = " . $uid);
        echo $_GET['username'];
        $avatar = file_get_contents($_GET['avatar_path']);
        return $user;
    }
}
?>`;
    scanSecurityVulnerabilities();
}

function copyModernizedCode() {
    navigator.clipboard.writeText(document.getElementById('modernizedCodeOutput').value);
    if (typeof showToast === 'function') showToast('Modernized code copied!', 'info');
}

function copyPatchedCode() {
    navigator.clipboard.writeText(document.getElementById('patchedCodeOutput').value);
    if (typeof showToast === 'function') showToast('Patched code copied!', 'info');
}

function runModernizerDemo() {
    triggerCodeUpgrade();
    scanSecurityVulnerabilities().then(() => {
        autoPatchSecurityVulnerabilities();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    runModernizerDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
