<?php
// ATOM Web Admin — Phase 58: Real-Time Audio Spectral Noise Subtraction & Acoustic Filter Rack
$pageTitle = "Acoustic Noise Filter (Phase 58)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Real-Time Audio Spectral Noise Subtraction &amp; Filter Rack</h2>
        <p class="text-muted small mb-0">Phase 58: FFT Spectral Subtraction Filter, Background Hum Removal, Signal-to-Noise Ratio (SNR) Estimator &amp; Tamil Acoustic Voice Enhancer</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-pink text-white fw-bold" style="background: #EC4899;" onclick="runDenoiseSimulation()">
            <i class="bi bi-soundwave me-1"></i> Process Noise Frame
        </button>
    </div>
</div>

<!-- Acoustic Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SNR BEFORE FILTER</div>
            <div class="fs-4 fw-bold text-danger" id="metricSnrBefore">8.2 dB (Noisy)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SNR AFTER FILTER</div>
            <div class="fs-4 fw-bold text-success" id="metricSnrAfter">22.4 dB (+14.2 dB)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">NOISE ATTENUATION</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricNoiseRed" style="color: #34D399;">90.0% REDUCED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">FILTER PRESET</div>
            <div class="fs-4 fw-bold text-pink-400" style="color: #EC4899;">Tamil Speech Studio</div>
        </div>
    </div>
</div>

<!-- Waveform Canvas & Filter Controls -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-activity me-2 text-pink-400"></i>FFT Spectrum &amp; Denoised Audio Waveform Visualizer</span>
                <span class="badge bg-success" id="filterStatusBadge">CLEANED ACOUSTICS</span>
            </div>
            <div class="card-body p-3 bg-black">
                <canvas id="waveformCanvas" class="w-100 rounded border border-secondary" height="200" style="background: #090c10;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-warning"><i class="bi bi-sliders me-2"></i>Acoustic Filter Rack Controls</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>OVER-SUBTRACTION (&alpha;)</span>
                        <span id="alphaValText" class="text-pink-400 fw-bold">1.8</span>
                    </label>
                    <input type="range" class="form-range" id="alphaRange" min="1.0" max="4.0" step="0.1" value="1.8" oninput="document.getElementById('alphaValText').innerText = this.value; runDenoiseSimulation();">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>SPECTRAL FLOOR (&beta;)</span>
                        <span id="betaValText" class="text-cyan-400 fw-bold">0.02</span>
                    </label>
                    <input type="range" class="form-range" id="betaRange" min="0.005" max="0.1" step="0.005" value="0.02" oninput="document.getElementById('betaValText').innerText = this.value; runDenoiseSimulation();">
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted mb-3">
                    <i class="bi bi-info-circle text-pink-400 me-1"></i> <strong>Spectral Subtraction ($|S(f)|$)</strong> removes steady microphone hum while the spectral floor ($\beta$) prevents musical noise chirping.
                </div>

                <button class="btn btn-sm text-white fw-bold w-100" style="background: #EC4899;" onclick="runDenoiseSimulation()">
                    <i class="bi bi-soundwave me-1"></i> Apply Spectral Subtraction
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function runDenoiseSimulation() {
    const alpha = parseFloat(document.getElementById('alphaRange').value);
    const beta = parseFloat(document.getElementById('betaRange').value);

    try {
        const res = await apiFetch('/voice/filter/denoise', {
            method: 'POST',
            body: JSON.stringify({ alpha: alpha, beta: beta })
        });

        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricSnrBefore').innerText = `${data.snr_before_db} dB (Noisy)`;
            document.getElementById('metricSnrAfter').innerText = `${data.snr_after_db} dB (+${data.snr_gain_db} dB)`;
            document.getElementById('metricNoiseRed').innerText = `${data.noise_reduced_pct}% REDUCED`;

            drawWaveform(data.cleaned_samples || []);
            if (typeof showToast === 'function') showToast(`Acoustics filtered: +${data.snr_gain_db} dB SNR Gain!`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Filter error: ' + e.message, 'error');
    }
}

function drawWaveform(samples) {
    const canvas = document.getElementById('waveformCanvas');
    const ctx = canvas.getContext('2d');
    const w = canvas.width = canvas.offsetWidth;
    const h = canvas.height = canvas.offsetHeight;

    ctx.clearRect(0, 0, w, h);

    // Draw center line
    ctx.strokeStyle = '#1e293b';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(0, h / 2);
    ctx.lineTo(w, h / 2);
    ctx.stroke();

    if (samples.length === 0) return;

    // Draw Waveform bars
    const barWidth = w / samples.length;
    samples.forEach((s, idx) => {
        const x = idx * barWidth;
        const barHeight = Math.abs(s) * (h / 2) * 0.9;
        const y = s >= 0 ? (h / 2) - barHeight : (h / 2);

        ctx.fillStyle = idx % 2 === 0 ? '#EC4899' : '#38BDF8';
        ctx.fillRect(x, y, barWidth - 1, barHeight);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    runDenoiseSimulation();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
