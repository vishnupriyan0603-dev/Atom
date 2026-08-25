<?php
// ATOM Web Admin — Phase 54: Autonomous AST Dead Code Eliminator & Unused Symbol Pruner
$pageTitle = "Dead Code Pruner (Phase 54)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #A855F7;">Autonomous AST Dead Code Eliminator &amp; Symbol Pruner</h2>
        <p class="text-muted small mb-0">Phase 54: Detection of Unreachable Statements, Unused Private Methods &amp; Dead Import Cleanups</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-primary fw-bold" onclick="runPrunerDemo()">
            <i class="bi bi-scissors me-1"></i> Run Pruner Demo
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-warning"><i class="bi bi-code-slash me-2"></i>Code with Dead Artifacts</span>
            </div>
            <div class="card-body">
                <textarea id="prunerInputCode" class="form-control bg-black text-white border-secondary small mb-3" rows="12" style="font-family: monospace; font-size: 12px;"><?php
use App\Models\UnusedLegacyModel;
use App\Helpers\ActiveHelper;

class OrderService {
    private function deadCalculation() {
        return 42 * 10;
    }

    public function process() {
        return "PROCESSED";
        $deadVar = "Will never execute";
    }
}
?></textarea>

                <div class="d-flex gap-2">
                    <button class="btn btn-warning text-dark fw-bold flex-grow-1" onclick="scanDeadCode()">
                        <i class="bi bi-search me-1"></i> Scan Dead Code
                    </button>
                    <button class="btn btn-primary fw-bold flex-grow-1" onclick="pruneDeadCode()">
                        <i class="bi bi-scissors me-1"></i> Prune Unused Symbols
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-success"><i class="bi bi-check2-circle me-2"></i>Cleaned AST Output</span>
            </div>
            <div class="card-body">
                <div id="deadCodeFindingsArea" class="p-2 mb-3 bg-black border border-secondary rounded text-xs">
                    Click 'Scan Dead Code' to inspect unused symbols.
                </div>
                <textarea id="prunerOutputCode" class="form-control bg-black text-emerald-400 border-secondary small" rows="12" style="font-family: monospace; font-size: 12px; color: #34D399;" readonly>// Pruned code will appear here...</textarea>
            </div>
        </div>
    </div>
</div>

<script>
async function scanDeadCode() {
    const code = document.getElementById('prunerInputCode').value;
    try {
        const res = await apiFetch('/refactoring/dead-code/scan', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });
        if (res && res.success) {
            const data = res.data;
            const container = document.getElementById('deadCodeFindingsArea');
            let html = `<div class="fw-bold mb-1 text-info">Found ${data.total_dead_items} dead items (Cleanliness Score: ${data.code_cleanliness_score}/100):</div>`;
            data.unused_imports.forEach(i => html += `<div class="text-warning">• Unused Import: <code>${escapeHtml(i.import)}</code></div>`);
            data.dead_symbols.forEach(s => html += `<div class="text-danger">• Unused Symbol: <code>${escapeHtml(s.symbol)}</code></div>`);
            data.unreachable_blocks.forEach(u => html += `<div class="text-danger">• Unreachable: <code>${escapeHtml(u.statement)}</code></div>`);
            container.innerHTML = html;
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Scan error: ' + e.message, 'error');
    }
}

async function pruneDeadCode() {
    const code = document.getElementById('prunerInputCode').value;
    try {
        const res = await apiFetch('/refactoring/dead-code/prune', {
            method: 'POST',
            body: JSON.stringify({ code: code })
        });
        if (res && res.success) {
            document.getElementById('prunerOutputCode').value = res.data.pruned_code;
            if (typeof showToast === 'function') showToast(`Successfully pruned ${res.data.pruned_items_count} dead artifacts!`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Prune error: ' + e.message, 'error');
    }
}

function runPrunerDemo() {
    scanDeadCode();
    pruneDeadCode();
}

document.addEventListener('DOMContentLoaded', () => {
    runPrunerDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
