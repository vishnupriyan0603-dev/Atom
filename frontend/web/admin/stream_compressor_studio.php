<?php
// ATOM Web Admin — Phase 90 Landmark: Autonomous Real-Time Zero-Copy Stream Compressor & Binary Frame Framer Crossbar
$pageTitle = "Stream Compressor & Wire Framer (Phase 90)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">Stream Compressor &amp; Wire Frame Crossbar</h2>
        <p class="text-muted small mb-0">Phase 90 Landmark: Multi-Codec Compression (DEFLATE / GZIP / Raw), 16-Byte Wire Protocol Header &amp; CRC32 Integrity Verification</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="compressStreamDemo()">
            <i class="bi bi-file-zip-fill me-1"></i> Pack &amp; Compress Frame
        </button>
    </div>
</div>

<!-- Stream Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMPRESSION RATIO</div>
            <div class="fs-4 fw-bold text-sky-400" id="metricRatio" style="color: #38BDF8;">3.45x</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">BANDWIDTH SAVED</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricSaved" style="color: #34D399;">71.0%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">WIRE PROTOCOL</div>
            <div class="fs-4 fw-bold text-cyan-400">16-Byte Header</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CHECKSUM INTEGRITY</div>
            <div class="fs-4 fw-bold text-info">CRC32 Verified</div>
        </div>
    </div>
</div>

<!-- Controls & Hex Inspector -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-sky-400"><i class="bi bi-sliders me-2"></i>Stream Packet Encoder</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PAYLOAD DATA</label>
                    <textarea id="payloadInput" class="form-control bg-black text-white border-secondary small font-monospace" rows="6">{
  "telemetry_stream": "atom-edge-mesh-node-42",
  "temperature_c": 42.8,
  "system_state": "OPTIMAL",
  "metrics": [10.2, 11.5, 14.8, 12.0, 9.8, 15.3, 11.2],
  "message": "Real-time edge event payload repeated for binary compression verification across the distributed fabric."
}</textarea>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">CODEC</label>
                        <select id="codecSelect" class="form-select bg-black text-white border-secondary small">
                            <option value="deflate" selected>DEFLATE (Fast Stream)</option>
                            <option value="gzip">GZIP (Container)</option>
                            <option value="raw">Raw (Uncompressed)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">LEVEL (1-9)</label>
                        <input type="number" id="levelInput" class="form-control bg-black text-white border-secondary small" min="1" max="9" value="6">
                    </div>
                </div>

                <button class="btn btn-sm btn-info text-dark fw-bold w-100" onclick="compressStreamDemo()">
                    <i class="bi bi-play-circle-fill me-1"></i> Compress &amp; Generate Binary Frame
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-binary me-2"></i>Hex Wire Protocol Inspector</span>
                <span class="badge bg-secondary" id="crcBadge">CRC32: --</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ENCODED HEX FRAME</label>
                    <div class="p-2 bg-black rounded border border-secondary text-xs font-monospace text-sky-400" id="hexFrameBox" style="max-height: 140px; overflow-y: auto; word-break: break-all;">
                        aa550101...
                    </div>
                </div>

                <button class="btn btn-sm btn-outline-light w-100 mb-3" onclick="decompressStreamDemo()">
                    <i class="bi bi-unlock-fill me-1"></i> Decompress &amp; Verify Integrity
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="decompressResultBox">
                    [Ready] Click 'Compress &amp; Generate Binary Frame' to test encoding...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let lastEncodedHex = '';

async function compressStreamDemo() {
    const payload = document.getElementById('payloadInput').value.trim();
    const codec = document.getElementById('codecSelect').value;
    const level = parseInt(document.getElementById('levelInput').value, 10) || 6;

    try {
        const res = await apiFetch('/network/stream/compress', {
            method: 'POST',
            body: JSON.stringify({ payload: payload, codec: codec, level: level })
        });

        if (res && res.success) {
            const d = res.data;
            lastEncodedHex = d.frame_hex;

            document.getElementById('metricRatio').innerText = `${d.compression_ratio}x`;
            document.getElementById('metricSaved').innerText = `${d.space_saved_pct}%`;
            document.getElementById('crcBadge').innerText = `CRC32: ${d.crc32_checksum}`;
            document.getElementById('hexFrameBox').innerText = d.frame_hex;

            document.getElementById('decompressResultBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[PACKED] Original: ${d.original_bytes} B &rarr; Frame: ${d.total_frame_bytes} B</div>
                <div class="text-white text-xs"><strong>Saved:</strong> ${d.space_saved_pct}% | <strong>Ratio:</strong> ${d.compression_ratio}x</div>
                <div class="text-muted text-xs">Sync Word: 0xAA55 | Magic: OK</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Packed frame (${d.compression_ratio}x ratio)`, 'success');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Compress error: ' + e.message, 'error');
    }
}

async function decompressStreamDemo() {
    if (!lastEncodedHex) {
        if (typeof showToast === 'function') showToast('Compress a frame first', 'warning');
        return;
    }

    try {
        const res = await apiFetch('/network/stream/decompress', {
            method: 'POST',
            body: JSON.stringify({ frame_hex: lastEncodedHex })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('decompressResultBox').innerHTML = `
                <div class="text-cyan-400 fw-bold mb-1">[VERIFIED] Checksum Match (0x${d.crc32_checksum})</div>
                <div class="text-white text-xs mb-1"><strong>Decompressed:</strong> ${d.decompressed_bytes} Bytes (${d.codec})</div>
                <div class="text-muted text-xs font-monospace">${escapeHtml(d.payload.substring(0, 100))}...</div>
            `;

            if (typeof showToast === 'function') {
                showToast('CRC32 integrity check passed', 'success');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Decompress error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    compressStreamDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
