<?php
// ATOM Web Admin — Phase 101: Autonomous WebAssembly (Wasm) Sandbox Runtime & Sandboxed Micro-Code Execution Engine
$pageTitle = "Wasm Sandbox Runtime (Phase 101)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #A855F7;">Wasm Sandbox &amp; Micro-Code Runtime</h2>
        <p class="text-muted small mb-0">Phase 101: Gas-Metered Execution, Linear Memory Isolation &amp; Fault-Trapped Bytecode Sandbox</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-primary fw-bold" style="background-color: #A855F7; border-color: #9333EA;" onclick="runWasmDemo()">
            <i class="bi bi-play-fill me-1"></i> Execute Sandbox
        </button>
    </div>
</div>

<!-- Wasm Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SANDBOX ISOLATION</div>
            <div class="fs-4 fw-bold text-purple-400" style="color: #C084FC;">Zero-IO Barrier</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">GAS METERING</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricGas">100,000 Cap</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">LINEAR MEMORY</div>
            <div class="fs-4 fw-bold text-cyan-400">64 MB Limit</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">FAULT TRAPPING</div>
            <div class="fs-4 fw-bold text-pink-400">Panic Isolation</div>
        </div>
    </div>
</div>

<!-- Sandbox Code Runner & Memory Heap Inspector -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-purple-400"><i class="bi bi-terminal-dash me-2"></i>Wasm Routine Dispatcher</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SELECT WASM MICRO-ROUTINE</label>
                    <select id="funcSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="vector_dot_product" selected>vector_dot_product (Vector Math)</option>
                        <option value="fast_hash_crc32">fast_hash_crc32 (Hashing)</option>
                        <option value="linear_memory_transform">linear_memory_transform (Bytecode Transform)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ARGUMENTS (JSON ARRAY)</label>
                    <textarea id="argsJsonInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="3">[[1.5, 2.5, 3.0], [2.0, 4.0, 1.0]]</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">GAS BUDGET</label>
                    <input type="number" id="gasInput" class="form-control bg-black text-white border-secondary small" value="10000" min="100" max="100000">
                </div>

                <button class="btn btn-sm btn-primary fw-bold w-100 mb-3" style="background-color: #A855F7; border-color: #9333EA;" onclick="runWasmDemo()">
                    <i class="bi bi-play-circle-fill me-1"></i> Execute in Isolated Sandbox
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-cyan-400"><i class="bi bi-cpu me-2"></i>Sandbox Execution Output</span>
                <span class="badge bg-secondary" id="statusBadge">READY</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="wasmResultBox">
                    [Ready] Click 'Execute in Isolated Sandbox' to run metered micro-code...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function runWasmDemo() {
    const func = document.getElementById('funcSelect').value;
    const gas = parseInt(document.getElementById('gasInput').value) || 10000;
    let args = [];
    try {
        args = JSON.parse(document.getElementById('argsJsonInput').value);
    } catch (e) {
        if (typeof showToast === 'function') showToast('Invalid arguments JSON', 'error');
        return;
    }

    try {
        const res = await apiFetch('/infrastructure/wasm/execute', {
            method: 'POST',
            body: JSON.stringify({
                function: func,
                args: args,
                gas_limit: gas
            })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('statusBadge').innerText = d.status;
            document.getElementById('statusBadge').className = d.status === 'COMPLETED' ? 'badge bg-success' : 'badge bg-danger';

            document.getElementById('wasmResultBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[RESULT] ${JSON.stringify(d.result)}</div>
                <div class="text-white text-xs mb-1"><strong>Gas Consumed:</strong> ${d.gas_consumed} / ${d.gas_limit} (${d.gas_remaining} remaining)</div>
                <div class="text-muted text-xs font-monospace">Execution Time: ${d.execution_time_ms} ms</div>
            `;

            if (typeof showToast === 'function') showToast(`Wasm routine executed: ${d.status}`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Wasm error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    runWasmDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
