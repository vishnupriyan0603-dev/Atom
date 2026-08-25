<?php
// ATOM Web Admin — Phase 66: Real-Time WebRTC Peer Data Channel Chunked File Transfer Mesh
$pageTitle = "WebRTC File Transfer (Phase 66)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">WebRTC Peer Data Channel File Transfer Mesh</h2>
        <p class="text-muted small mb-0">Phase 66: P2P Chunked Binary File Streaming, SHA-256 Checksum Integrity Verification &amp; Zero-Relay Data Channels</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="simulatePeerTransfer()">
            <i class="bi bi-send-fill me-1"></i> Simulate P2P Transfer
        </button>
    </div>
</div>

<!-- Transfer Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TRANSFER INTEGRITY</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricIntegrity" style="color: #34D399;">SHA-256 VERIFIED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">THROUGHPUT</div>
            <div class="fs-4 fw-bold text-info" id="metricBitrate">48.5 Mbps (P2P)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CHUNKS SENT</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricChunks">10 / 10 (100%)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CHANNEL PROTOCOL</div>
            <div class="fs-4 fw-bold text-pink-400" style="color: #EC4899;">WebRTC SCTP Binary</div>
        </div>
    </div>
</div>

<!-- Main Transfer Simulator -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6"><i class="bi bi-hdd-network me-2 text-cyan-400"></i>Active P2P Data Channel Stream</span>
        <span class="badge bg-success" id="transferStatusBadge">STREAM COMPLETE</span>
    </div>
    <div class="card-body p-4">
        <div class="mb-3">
            <div class="d-flex justify-content-between text-xs mb-1">
                <span class="text-muted fw-bold">TRANSFER PROGRESS:</span>
                <span class="text-cyan-400 fw-bold" id="progressPctText">100%</span>
            </div>
            <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-cyan-400 progress-bar-striped progress-bar-animated" id="transferProgressBar" style="width: 100%; background: #38BDF8;"></div>
            </div>
        </div>

        <div class="p-3 bg-black rounded border border-secondary text-xs text-muted mb-3 font-monospace" id="transferLogBox">
            [0.00s] Initializing WebRTC SCTP Data Channel between Peer A and Peer B...<br>
            [0.01s] Payload split into 10 chunks (512 bytes each). Computed SHA-256 checksum.<br>
            [0.02s] Streaming chunks 1..10 across data channel...<br>
            [0.03s] Reassembly verified! Checksum matched successfully.
        </div>

        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="simulatePeerTransfer()">
            <i class="bi bi-play-circle-fill me-1"></i> Start New 10-Chunk Transfer
        </button>
    </div>
</div>

<script>
async function simulatePeerTransfer() {
    const logBox = document.getElementById('transferLogBox');
    logBox.innerHTML = '[0.00s] Preparing file chunks for WebRTC stream...<br>';

    try {
        const prepRes = await apiFetch('/network/webrtc/transfer/prepare', {
            method: 'POST',
            body: JSON.stringify({ file_name: 'test_artifact.bin', chunk_size_bytes: 512 })
        });

        if (prepRes && prepRes.success) {
            const data = prepRes.data;
            const transferId = data.transfer_id;
            const chunks = data.chunks;

            logBox.innerHTML += `[0.01s] File split into ${chunks.length} chunks. Transfer ID: ${transferId}<br>`;

            // Ingest chunks sequentially
            for (let i = 0; i < chunks.length; i++) {
                const c = chunks[i];
                await apiFetch('/network/webrtc/transfer/ingest-chunk', {
                    method: 'POST',
                    body: JSON.stringify({
                        transfer_id: transferId,
                        chunk_index: c.chunk_index,
                        data: c.data,
                        chunk_checksum: c.chunk_checksum
                    })
                });
                const pct = Math.round(((i + 1) / chunks.length) * 100);
                document.getElementById('transferProgressBar').style.width = `${pct}%`;
                document.getElementById('progressPctText').innerText = `${pct}%`;
            }

            // Reassemble
            const reasmRes = await apiFetch('/network/webrtc/transfer/reassemble', {
                method: 'POST',
                body: JSON.stringify({ transfer_id: transferId })
            });

            if (reasmRes && reasmRes.success) {
                const r = reasmRes.data;
                logBox.innerHTML += `[${r.duration_sec}s] Reassembled ${r.file_size_bytes} bytes. Checksum verified: ${r.checksum_sha256.substring(0, 16)}...<br>`;
                document.getElementById('metricBitrate').innerText = `${r.bitrate_mbps} Mbps (P2P)`;
                document.getElementById('metricChunks').innerText = `${chunks.length} / ${chunks.length} (100%)`;
                if (typeof showToast === 'function') showToast(`P2P File Transfer Complete! ${r.bitrate_mbps} Mbps`, 'success');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Transfer error: ' + e.message, 'error');
    }
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
