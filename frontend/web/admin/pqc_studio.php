<?php
// ATOM Web Admin — Phase 45: Post-Quantum Cryptography & Zero-Trust Studio
$pageTitle = "Post-Quantum Cryptography & Zero-Trust (Phase 45)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Post-Quantum Cryptography &amp; Zero-Trust Key Exchange</h2>
        <p class="text-muted small mb-0">Phase 45: Module Lattice-Based Key Encapsulation (MLWE / Kyber-768), Quantum Digital Signatures (Dilithium-5) &amp; Hybrid Quantum-Resistant Zero-Trust Mesh Handshake</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: linear-gradient(135deg, #EC4899 0%, #BE185D 100%); border: none;" onclick="runPqcFullDemo()">
            <i class="bi bi-shield-check me-1"></i> Run Quantum Audit Demo
        </button>
    </div>
</div>

<!-- PQC Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">QUANTUM SECURITY LEVEL</div>
            <div class="fs-4 fw-bold text-success">NIST LEVEL 5 (256-Bit)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">LATTICE KEM ALGORITHM</div>
            <div class="fs-4 fw-bold text-info">ATOM-MLWE-KEM-768</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DIGITAL SIGNATURES</div>
            <div class="fs-4 fw-bold text-warning">ATOM-MLWE-SIG-5</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HYBRID HANDSHAKE</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">X25519 + MLWE768</div>
        </div>
    </div>
</div>

<!-- Main Tabs -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary p-2">
        <ul class="nav nav-pills card-header-pills" id="pqcStudioTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active text-white fw-bold py-2 px-3" id="tabKemBtn" data-bs-toggle="pill" data-bs-target="#tabKem" type="button">
                    <i class="bi bi-key-fill me-1 text-info"></i> 1. Lattice Key Encapsulation (KEM)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-white fw-bold py-2 px-3" id="tabSigBtn" data-bs-toggle="pill" data-bs-target="#tabSig" type="button">
                    <i class="bi bi-pen-fill me-1 text-warning"></i> 2. Quantum Digital Signatures
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-white fw-bold py-2 px-3" id="tabHandshakeBtn" data-bs-toggle="pill" data-bs-target="#tabHandshake" type="button">
                    <i class="bi bi-shield-lock-fill me-1 text-pink-400" style="color: #EC4899;"></i> 3. Hybrid Zero-Trust Handshake
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4">
        <div class="tab-content" id="pqcStudioTabContent">
            
            <!-- TAB 1: Key Encapsulation Mechanism (KEM) -->
            <div class="tab-pane fade show active" id="tabKem" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-info mb-0"><i class="bi bi-cpu me-2"></i>Quantum Keypair Generator</h5>
                            <button class="btn btn-sm btn-info text-dark fw-bold" onclick="generatePqcKeypair()">
                                <i class="bi bi-plus-lg me-1"></i> Generate New Keypair
                            </button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">POST-QUANTUM PUBLIC KEY ($PK$)</label>
                            <textarea id="pqcPublicKeyArea" class="form-control bg-black text-white border-secondary small" rows="5" style="font-family: monospace; font-size: 11px;" readonly>// Click 'Generate New Keypair' to synthesize MLWE-768 lattice polynomials...</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">POST-QUANTUM SECRET KEY ($SK$)</label>
                            <textarea id="pqcSecretKeyArea" class="form-control bg-black text-white border-secondary small" rows="3" style="font-family: monospace; font-size: 11px;" readonly>// Secret noise vector $s$...</textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-warning text-dark fw-bold flex-grow-1" onclick="encapsulateSecret()">
                                <i class="bi bi-lock-fill me-1"></i> Encapsulate Shared Secret
                            </button>
                            <button class="btn btn-success fw-bold flex-grow-1" onclick="decapsulateSecret()">
                                <i class="bi bi-unlock-fill me-1"></i> Decapsulate Shared Secret
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="fw-bold text-emerald-400 mb-3" style="color: #34D399;"><i class="bi bi-file-earmark-lock2 me-2"></i>Ciphertext &amp; Derived Session Key</h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">QUANTUM CIPHERTEXT ($C$)</label>
                            <textarea id="pqcCiphertextArea" class="form-control bg-black text-info border-secondary small" rows="5" style="font-family: monospace; font-size: 11px;" readonly>// Encapsulated ciphertext will appear here...</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">RECOVERED QUANTUM SHARED SECRET ($K$)</label>
                            <input type="text" id="pqcSharedSecretOutput" class="form-control bg-black text-emerald-400 border-secondary fw-bold" style="font-family: monospace;" readonly value="N/A (Pending Encapsulation)">
                        </div>

                        <div>
                            <label class="form-label text-muted small fw-bold">DERIVED AES-256-GCM SYMMETRIC SESSION KEY</label>
                            <input type="text" id="pqcDerivedAesOutput" class="form-control bg-black text-warning border-secondary fw-bold" style="font-family: monospace;" readonly value="N/A (Pending HKDF)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Quantum Digital Signatures -->
            <div class="tab-pane fade" id="tabSig" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h5 class="fw-bold text-warning mb-3"><i class="bi bi-pen me-2"></i>Lattice Signer Console</h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">MESSAGE / SWARM WORK ORDER PAYLOAD</label>
                            <textarea id="sigMessageInput" class="form-control bg-black text-white border-secondary" rows="4" style="font-family: monospace; font-size: 13px;">{"work_order_id": "wo_99218", "action": "deploy_secure_mesh", "initiator": "atom_swarm_master", "timestamp": 1787639800}</textarea>
                        </div>

                        <button class="btn btn-warning text-dark fw-bold w-100 mb-3" onclick="signMessage()">
                            <i class="bi bi-shield-shaded me-1"></i> Sign with Post-Quantum Lattice Key
                        </button>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">SYNTHESIZED QUANTUM SIGNATURE ($\sigma$)</label>
                            <textarea id="sigOutputArea" class="form-control bg-black text-warning border-secondary small" rows="5" style="font-family: monospace; font-size: 11px;" readonly>// Signature will be generated here...</textarea>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="fw-bold text-info mb-3"><i class="bi bi-patch-check me-2"></i>Quantum Signature Verifier</h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">VERIFICATION STATUS</label>
                            <div id="sigVerificationStatusBox" class="p-3 bg-black border border-secondary rounded text-muted">
                                Ready to verify signature...
                            </div>
                        </div>

                        <button class="btn btn-info text-dark fw-bold w-100" onclick="verifySignature()">
                            <i class="bi bi-check-circle-fill me-1"></i> Authenticate Digital Signature
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Hybrid Zero-Trust Handshake -->
            <div class="tab-pane fade" id="tabHandshake" role="tabpanel">
                <div class="row g-4">
                    <div class="col-md-5">
                        <h5 class="fw-bold text-pink-400 mb-3" style="color: #EC4899;"><i class="bi bi-broadcast-pin me-2"></i>Peer Node Handshake Initiation</h5>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">CLIENT NODE ID</label>
                            <input type="text" id="handshakeNodeId" class="form-control bg-black text-white border-secondary" value="edge_swarm_peer_07">
                        </div>

                        <button class="btn btn-primary fw-bold w-100" onclick="executeHybridHandshake()">
                            <i class="bi bi-arrow-left-right me-1"></i> Establish Zero-Trust Hybrid Tunnel
                        </button>
                    </div>

                    <div class="col-md-7">
                        <h5 class="fw-bold text-info mb-3"><i class="bi bi-hdd-network me-2"></i>Handshake Session &amp; Composite Key</h5>
                        <div id="handshakeResultArea" class="p-3 bg-black border border-secondary rounded small" style="font-family: monospace; color: #34D399; min-height: 240px; white-space: pre-wrap;">
Click 'Establish Zero-Trust Hybrid Tunnel' to run composite X25519 + MLWE768 key exchange.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
let currentPqcKeypair = null;
let currentCiphertext = null;
let currentSigVerificationKey = null;

async function generatePqcKeypair() {
    try {
        const res = await apiFetch('/pqc/kem/keypair', { method: 'POST' });
        if (res && res.success) {
            currentPqcKeypair = res.data;
            document.getElementById('pqcPublicKeyArea').value = currentPqcKeypair.public_key;
            document.getElementById('pqcSecretKeyArea').value = currentPqcKeypair.secret_key;
            if (typeof showToast === 'function') showToast(`Post-Quantum MLWE Keypair Generated (${currentPqcKeypair.fingerprint})`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Keypair generation error: ' + e.message, 'error');
    }
}

async function encapsulateSecret() {
    if (!currentPqcKeypair) await generatePqcKeypair();

    try {
        const res = await apiFetch('/pqc/kem/encapsulate', {
            method: 'POST',
            body: JSON.stringify({ public_key: currentPqcKeypair.public_key })
        });

        if (res && res.success) {
            currentCiphertext = res.data.ciphertext;
            document.getElementById('pqcCiphertextArea').value = currentCiphertext;
            document.getElementById('pqcSharedSecretOutput').value = res.data.shared_secret;
            document.getElementById('pqcDerivedAesOutput').value = res.data.derived_aes_key;
            if (typeof showToast === 'function') showToast('Quantum Shared Secret Encapsulated', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Encapsulation error: ' + e.message, 'error');
    }
}

async function decapsulateSecret() {
    if (!currentCiphertext || !currentPqcKeypair) {
        if (typeof showToast === 'function') showToast('Encapsulate first to generate ciphertext', 'warning');
        return;
    }

    try {
        const res = await apiFetch('/pqc/kem/decapsulate', {
            method: 'POST',
            body: JSON.stringify({ ciphertext: currentCiphertext, secret_key: currentPqcKeypair.secret_key })
        });

        if (res && res.success) {
            document.getElementById('pqcSharedSecretOutput').value = res.data.shared_secret;
            document.getElementById('pqcDerivedAesOutput').value = res.data.derived_aes_key;
            if (typeof showToast === 'function') showToast('Quantum Shared Secret Decapsulated & Verified!', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Decapsulation error: ' + e.message, 'error');
    }
}

async function signMessage() {
    const msg = document.getElementById('sigMessageInput').value;
    try {
        const res = await apiFetch('/pqc/sign', {
            method: 'POST',
            body: JSON.stringify({ message: msg })
        });

        if (res && res.success) {
            document.getElementById('sigOutputArea').value = res.data.signature;
            currentSigVerificationKey = res.data.verification_key;
            if (typeof showToast === 'function') showToast('Message Signed with Post-Quantum Lattice Signature', 'success');
            verifySignature();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Signing error: ' + e.message, 'error');
    }
}

async function verifySignature() {
    const msg = document.getElementById('sigMessageInput').value;
    const sig = document.getElementById('sigOutputArea').value;

    if (!sig || !currentSigVerificationKey) return;

    try {
        const res = await apiFetch('/pqc/verify', {
            method: 'POST',
            body: JSON.stringify({ message: msg, signature: sig, verification_key: currentSigVerificationKey })
        });

        if (res && res.success) {
            const box = document.getElementById('sigVerificationStatusBox');
            if (res.data.valid) {
                box.className = 'p-3 bg-black border border-success rounded text-success fw-bold';
                box.innerHTML = '✅ SIGNATURE AUTHENTICATED (NIST Level 5 Validated)';
            } else {
                box.className = 'p-3 bg-black border border-danger rounded text-danger fw-bold';
                box.innerHTML = '❌ SIGNATURE FORGERY / INVALID';
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Verification error: ' + e.message, 'error');
    }
}

async function executeHybridHandshake() {
    const nodeId = document.getElementById('handshakeNodeId').value;
    try {
        const res = await apiFetch('/pqc/handshake', {
            method: 'POST',
            body: JSON.stringify({ node_id: nodeId })
        });

        if (res && res.success) {
            const area = document.getElementById('handshakeResultArea');
            area.innerText = `🛡️ [Zero-Trust Hybrid Tunnel Established]\n`
                + `• Suite: ${res.data.cipher_suite}\n`
                + `• Session Token: ${res.data.response.session_token}\n`
                + `• Initiator Peer: ${res.data.initiation.initiator_node}\n`
                + `• Responder Peer: ${res.data.response.responder_node}\n`
                + `• Classical ECDH Secret: ${res.data.initiation.classical_public.substring(0, 24)}...\n`
                + `• Post-Quantum KEM Secret: MLWE-768 Encapsulated (Active)\n`
                + `• Composite Session Key: ${res.data.response.hybrid_session_key}\n`
                + `• Status: QUANTUM-RESISTANT TUNNEL ACTIVE`;
            if (typeof showToast === 'function') showToast('Zero-Trust Hybrid Tunnel Active!', 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Handshake error: ' + e.message, 'error');
    }
}

function runPqcFullDemo() {
    generatePqcKeypair().then(() => {
        encapsulateSecret().then(() => {
            decapsulateSecret();
        });
    });
    signMessage();
    executeHybridHandshake();
}

document.addEventListener('DOMContentLoaded', () => {
    runPqcFullDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
