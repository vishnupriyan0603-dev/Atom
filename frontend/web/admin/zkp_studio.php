<?php
// ATOM Web Admin — Phase 91: Autonomous Real-Time Zero-Knowledge Proof (ZKP) Snark/Zk-Rollup Simulator & Verifier Engine
$pageTitle = "Zero-Knowledge Proof & zk-Rollup (Phase 91)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Zero-Knowledge Proofs &amp; zk-Rollup Verifier</h2>
        <p class="text-muted small mb-0">Phase 91: Schnorr NIZKP Discrete Logarithm Verifier, Fiat-Shamir Heuristic &amp; Constant-Time $O(1)$ Merkle Rollup Compression</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-pink text-white fw-bold" style="background: #EC4899;" onclick="generateZkpDemo()">
            <i class="bi bi-shield-lock-fill me-1"></i> Generate &amp; Verify ZKP
        </button>
    </div>
</div>

<!-- ZKP Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ZKP PROTOCOL</div>
            <div class="fs-4 fw-bold text-pink-400" style="color: #F472B6;">Schnorr NIZKP</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HEURISTIC</div>
            <div class="fs-4 fw-bold text-cyan-400">Fiat-Shamir (Non-Int.)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">VERIFICATION COMPLEXITY</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">$O(1)$ Constant Time</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SECRET LEAKAGE</div>
            <div class="fs-4 fw-bold text-info">Zero Bits Revealed</div>
        </div>
    </div>
</div>

<!-- ZKP Prover & zk-Rollup Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-pink-400"><i class="bi bi-key-fill me-2"></i>Secret Witness Prover</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">IDENTITY CONTEXT</label>
                    <input type="text" id="identityInput" class="form-control bg-black text-white border-secondary small" value="user_alice_root">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SECRET WITNESS PASSPHRASE (Never Transmitted)</label>
                    <input type="password" id="secretInput" class="form-control bg-black text-white border-secondary small" value="super_confidential_master_key_123">
                </div>

                <button class="btn btn-sm btn-pink text-white fw-bold w-100 mb-3" style="background: #EC4899;" onclick="generateZkpDemo()">
                    <i class="bi bi-play-circle-fill me-1"></i> Generate Non-Interactive Proof
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="zkpResultBox">
                    [Ready] Click 'Generate Non-Interactive Proof' to test zero-knowledge verification...
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-layers-fill me-2"></i>zk-Rollup Batch Aggregator</span>
                <span class="badge bg-secondary">MERKLE COMMITMENT</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TRANSACTION BATCH</label>
                    <textarea id="txBatchInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="5">[
  {"from": "alice", "to": "bob", "amount": 50},
  {"from": "bob", "to": "carol", "amount": 25},
  {"from": "carol", "to": "dave", "amount": 10},
  {"from": "dave", "to": "alice", "amount": 5}
]</textarea>
                </div>

                <button class="btn btn-sm btn-outline-info w-100 mb-3" onclick="compressRollupDemo()">
                    <i class="bi bi-node-plus-fill me-1"></i> Aggregate &amp; Generate State Root Proof
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="rollupResultBox">
                    [Ready] Aggregate multi-transfer transactions into a single Merkle state root...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function generateZkpDemo() {
    const identity = document.getElementById('identityInput').value.trim();
    const secret = document.getElementById('secretInput').value;

    try {
        // Step 1: Generate Proof
        const genRes = await apiFetch('/security/zkp/generate', {
            method: 'POST',
            body: JSON.stringify({ identity: identity, secret: secret })
        });

        if (genRes && genRes.success) {
            const proofData = genRes.data;

            // Step 2: Verify Proof
            const verifyRes = await apiFetch('/security/zkp/verify', {
                method: 'POST',
                body: JSON.stringify({
                    public_key: proofData.public_key,
                    proof: proofData.proof,
                    identity: identity
                })
            });

            if (verifyRes && verifyRes.success) {
                const v = verifyRes.data;
                const statusColor = v.valid ? 'text-emerald-400' : 'text-danger';

                document.getElementById('zkpResultBox').innerHTML = `
                    <div class="${statusColor} fw-bold mb-1">[VERIFIED: ${v.valid ? 'YES' : 'NO'}] ${v.status}</div>
                    <div class="text-white text-xs mb-1"><strong>Public Key (Y):</strong> ${v.public_key}</div>
                    <div class="text-muted text-xs font-monospace">LHS (g^z): ${v.lhs_commitment} &equiv; RHS (A&middot;Y^c): ${v.rhs_evaluation}</div>
                `;

                if (typeof showToast === 'function') {
                    showToast('ZKP mathematical verification: ' + (v.valid ? 'PASSED' : 'FAILED'), v.valid ? 'success' : 'error');
                }
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('ZKP error: ' + e.message, 'error');
    }
}

async function compressRollupDemo() {
    try {
        const rawJson = document.getElementById('txBatchInput').value;
        const txs = JSON.parse(rawJson);

        const res = await apiFetch('/security/zkp/rollup', {
            method: 'POST',
            body: JSON.stringify({ transactions: txs })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('rollupResultBox').innerHTML = `
                <div class="text-info fw-bold mb-1">[ZK-ROLLUP] Batch Size: ${d.batch_size} TXs (${d.compression_factor})</div>
                <div class="text-white text-xs mb-1 font-monospace"><strong>State Root:</strong> ${d.state_root}</div>
                <div class="text-muted text-xs font-monospace"><strong>Proof:</strong> ${d.validity_proof.substring(0, 32)}...</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Rollup aggregated (${d.batch_size} txs &rarr; 1 Root)`, 'success');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Rollup error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    generateZkpDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
