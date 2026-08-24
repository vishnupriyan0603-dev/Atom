<?php
// ATOM Web Admin — Phase 33: Federated Zero-Knowledge Vault & Sync Dashboard
$pageTitle = "Zero-Knowledge Vault & Sync";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Federated Zero-Knowledge Vault &amp; Sync</h2>
        <p class="text-muted small mb-0">End-to-end client-side AES-256-GCM encryption, PBKDF2 derivation, Merkle audit chains &amp; peer sync</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white" style="background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%); border: none;" onclick="loadMerkleRoot()">
            <i class="bi bi-shield-check me-1"></i> Verify Merkle Root
        </button>
    </div>
</div>

<!-- Vault Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">VAULT GATE</div>
            <div class="fs-4 fw-bold" style="color:#EC4899;" id="metricVaultState">UNLOCKED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CIPHER ALGORITHM</div>
            <div class="fs-4 fw-bold text-info" id="metricCipher">AES-256-GCM</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">KEY DERIVATION</div>
            <div class="fs-4 fw-bold text-success" id="metricKdf">PBKDF2 (10k iter)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MERKLE INTEGRITY</div>
            <div class="fs-4 fw-bold text-warning" id="metricMerkle">VERIFIED (0 Leafs)</div>
        </div>
    </div>
</div>

<!-- Vault Operations Grid -->
<div class="row g-4 mb-4">
    <!-- 1. Encrypt & Store -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#EC4899;"><i class="bi bi-lock me-2"></i>Zero-Knowledge Encrypt &amp; Store</span>
                <span class="badge bg-pink text-white" style="background-color:#EC4899;" id="storeBadge">READY</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">VAULT RECORD KEY</label>
                    <input type="text" id="storeKey" class="form-control bg-black text-white border-secondary mb-2" value="user_private_notes" placeholder="e.g. api_keys, user_notes">
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PLAINTEXT SECRET</label>
                    <textarea id="storePlaintext" class="form-control bg-black text-white border-secondary" rows="3" placeholder="Enter sensitive secret data to encrypt...">Client-side confidential document #4092</textarea>
                </div>
                <button class="btn btn-sm text-white fw-bold w-100 mb-3" style="background: linear-gradient(135deg, #EC4899 0%, #DB2777 100%);" onclick="storeVaultRecord()">
                    <i class="bi bi-shield-lock-fill me-1"></i> Encrypt &amp; Append to Merkle Tree
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 120px;">
                    <div class="text-muted small fw-bold mb-1">ENCRYPTED CIPHERTEXT PACKET:</div>
                    <div id="storeOutput" class="small text-emerald-400" style="font-family: monospace; white-space: pre-wrap; color:#34D399;">
Ready for encryption.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Retrieve & Decrypt -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-unlock me-2"></i>Retrieve &amp; Decrypt Record</span>
                <span class="badge bg-info text-dark" id="retrieveBadge">READY</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">VAULT RECORD KEY</label>
                    <div class="input-group">
                        <input type="text" id="retrieveKey" class="form-control bg-black text-white border-secondary" value="user_private_notes" placeholder="Key to fetch">
                        <button class="btn btn-info text-dark fw-bold" onclick="retrieveVaultRecord()">Decrypt</button>
                    </div>
                </div>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 235px;">
                    <div class="text-muted small fw-bold mb-2">DECRYPTED PLAINTEXT:</div>
                    <div id="retrieveOutput" class="small text-white" style="font-family: monospace; white-space: pre-wrap;">
Click 'Decrypt' to fetch and verify the authenticated ciphertext.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Merkle Audit Tree Chain -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-warning"><i class="bi bi-diagram-3 me-2"></i>Cryptographic Merkle Audit Tree Chain</span>
        <span class="badge bg-warning text-dark" id="merkleLeafBadge">0 LEAVES</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">CURRENT MERKLE ROOT HASH (H_root)</label>
                <input type="text" id="merkleRootInput" class="form-control bg-black text-warning border-secondary font-monospace small" readonly value="Calculating...">
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small fw-bold">DIFFERENTIAL PEER SYNC STATUS</label>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-warning w-100" onclick="triggerPeerSync()">
                        <i class="bi bi-arrow-repeat me-1"></i> Sync Peer Deltas
                    </button>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label text-muted small fw-bold">RECENT AUDIT LEAF HASHES</label>
            <div class="p-3 bg-black border border-secondary rounded font-monospace small" id="merkleLeavesOutput" style="max-height: 150px; overflow-y: auto; color:#F59E0B;">
No leaves recorded yet.
            </div>
        </div>
    </div>
</div>

<script>
async function storeVaultRecord() {
    const key = document.getElementById('storeKey').value;
    const val = document.getElementById('storePlaintext').value;
    document.getElementById('storeBadge').innerText = 'ENCRYPTING...';
    try {
        const res = await fetch('/api/v1/vault/store', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({key: key, value: val})
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('storeBadge').innerText = 'STORED';
            document.getElementById('storeOutput').innerText = JSON.stringify(data.data, null, 2);
            loadMerkleRoot();
        } else {
            document.getElementById('storeBadge').innerText = 'ERROR';
            document.getElementById('storeOutput').innerText = 'Error: ' + data.message;
        }
    } catch (e) {
        document.getElementById('storeOutput').innerText = 'Network error: ' + e.message;
    }
}

async function retrieveVaultRecord() {
    const key = document.getElementById('retrieveKey').value;
    document.getElementById('retrieveBadge').innerText = 'DECRYPTING...';
    try {
        const res = await fetch('/api/v1/vault/retrieve', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({key: key})
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('retrieveBadge').innerText = 'DECRYPTED';
            document.getElementById('retrieveOutput').innerText = `Key: ${data.data.key}\nDecrypted Value:\n${data.data.value}\n\nVector Clock: ${data.data.clock}`;
        } else {
            document.getElementById('retrieveBadge').innerText = 'NOT FOUND';
            document.getElementById('retrieveOutput').innerText = 'Error: ' + data.message;
        }
    } catch (e) {
        document.getElementById('retrieveOutput').innerText = 'Error: ' + e.message;
    }
}

async function loadMerkleRoot() {
    try {
        const res = await fetch('/api/v1/vault/merkle-root');
        const data = await res.json();
        if (data.success) {
            const root = data.data.root_hash || 'None (Empty)';
            const count = data.data.total_leaves;
            document.getElementById('merkleRootInput').value = root;
            document.getElementById('metricMerkle').innerText = `VERIFIED (${count} Leafs)`;
            document.getElementById('merkleLeafBadge').innerText = `${count} LEAVES`;
            document.getElementById('merkleLeavesOutput').innerText = data.data.leaves.length 
                ? data.data.leaves.map((l, i) => `Leaf [${i}]: ${l}`).join('\n')
                : 'No leaves recorded yet.';
        }
    } catch (e) {}
}

async function triggerPeerSync() {
    try {
        const res = await fetch('/api/v1/vault/sync-deltas', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({since_clock: 0})
        });
        const data = await res.json();
        if (data.success) {
            alert(`Differential Sync Complete!\nOutgoing Deltas: ${data.data.outgoing_deltas.length}\nMerkle Root: ${data.data.merkle_root || 'Clean'}`);
            loadMerkleRoot();
        }
    } catch (e) {
        alert('Sync failed: ' + e.message);
    }
}

// Initial load
document.addEventListener('DOMContentLoaded', () => loadMerkleRoot());
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
