<?php
// ATOM Web Admin — Phase 59: Autonomous Git VCS Semantic Release & Changelog Synthesizer
$pageTitle = "Semantic Release Studio (Phase 59)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #14B8A6;">Autonomous Git VCS Semantic Release &amp; Changelog Studio</h2>
        <p class="text-muted small mb-0">Phase 59: Conventional Commit Analyzer, Automated Semantic Versioning ($MAJOR.$MINOR.$PATCH$) &amp; Markdown Changelog Synthesizer</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-teal text-white fw-bold" style="background: #14B8A6;" onclick="analyzeSemanticRelease()">
            <i class="bi bi-git me-1"></i> Analyze Next Tag
        </button>
    </div>
</div>

<!-- Release Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CURRENT VERSION</div>
            <div class="fs-4 fw-bold text-muted">v2.0.0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMPUTED NEXT TAG</div>
            <div class="fs-4 fw-bold text-teal-400" id="metricNextTag" style="color: #2DD4BF;">v2.1.0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">BUMP TYPE</div>
            <div class="fs-4 fw-bold text-warning" id="metricBumpType">MINOR (Features Added)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMMITS PROCESSED</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricCommitsCount" style="color: #34D399;">5 COMMITS</div>
        </div>
    </div>
</div>

<!-- Commit Analyzer & Changelog Preview -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-teal-400" style="color: #2DD4BF;"><i class="bi bi-list-check me-2"></i>Conventional Commits to Analyze</span>
            </div>
            <div class="card-body">
                <textarea id="commitsInputArea" class="form-control bg-black text-white border-secondary small mb-3" rows="10" style="font-family: monospace; font-size: 12px;">feat(phase56): add multi-tenant zero-trust token bucket rate limiter
feat(phase57): add autonomous OpenAPI 3.1 schema and multi-language SDK generator
feat(phase58): add real-time audio spectral noise filter and acoustic SNR rack
fix(admin): optimize sidebar scrolling, live menu filter search, and active route highlighting
perf(engine): optimize AST complexity scanner to O(N) hash map lookups</textarea>

                <button class="btn text-white fw-bold w-100" style="background: #14B8A6;" onclick="analyzeSemanticRelease()">
                    <i class="bi bi-tag-fill me-1"></i> Compute SemVer &amp; Synthesize Release Notes
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-markdown-fill me-2"></i>Synthesized Markdown Changelog</span>
                <button class="btn btn-xs btn-outline-secondary" onclick="copyChangelog()"><i class="bi bi-clipboard me-1"></i>Copy Markdown</button>
            </div>
            <div class="card-body">
                <textarea id="changelogOutputArea" class="form-control bg-black text-emerald-400 border-secondary small" rows="10" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>Click 'Compute SemVer' to synthesize release notes...</textarea>
            </div>
        </div>
    </div>
</div>

<script>
async function analyzeSemanticRelease() {
    const rawCommits = document.getElementById('commitsInputArea').value.split('\n').filter(c => c.trim().length > 0);

    try {
        const res = await apiFetch('/vcs/release/analyze', {
            method: 'POST',
            body: JSON.stringify({ commits: rawCommits, current_version: 'v2.0.0' })
        });

        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricNextTag').innerText = data.next_version;
            document.getElementById('metricBumpType').innerText = `${data.bump_type} (${data.bump_type === 'MAJOR' ? 'Breaking Changes' : (data.bump_type === 'MINOR' ? 'New Features' : 'Bug Fixes')})`;
            document.getElementById('metricCommitsCount').innerText = `${data.total_commits_analyzed} COMMITS`;

            document.getElementById('changelogOutputArea').value = data.changelog_markdown;
            if (typeof showToast === 'function') showToast(`Computed Next SemVer: ${data.next_version} (${data.bump_type})`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Release analysis error: ' + e.message, 'error');
    }
}

function copyChangelog() {
    navigator.clipboard.writeText(document.getElementById('changelogOutputArea').value);
    if (typeof showToast === 'function') showToast('Changelog markdown copied!', 'info');
}

document.addEventListener('DOMContentLoaded', () => {
    analyzeSemanticRelease();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
