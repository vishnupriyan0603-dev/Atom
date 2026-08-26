<?php
// ATOM Web Admin — Phase 88: Real-Time Audio Dynamic Range Compressor & Psychoacoustic Limiter Engine
$pageTitle = "Audio Compressor & Limiter (Phase 88)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Audio Dynamic Range Compressor &amp; Peak Limiter</h2>
        <p class="text-muted small mb-0">Phase 88: Multi-Stage Compression, Psychoacoustic Peak Limiting, Knee Ballistics &amp; Makeup Gain Normalization</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-pink text-white fw-bold" style="background: #EC4899;" onclick="processCompressionDemo()">
            <i class="bi bi-soundwave me-1"></i> Process Audio Buffer
        </button>
    </div>
</div>

<!-- Compressor Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MAX GAIN REDUCTION</div>
            <div class="fs-4 fw-bold text-pink-400" id="metricGR" style="color: #F472B6;">-4.2 dB</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PEAK OUTPUT CEILING</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricPeakOut" style="color: #34D399;">-0.10 dBFS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">COMPRESSION RATIO</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricRatio">4.0 : 1</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ANTI-CLIPPING</div>
            <div class="fs-4 fw-bold text-info">True-Peak Safe</div>
        </div>
    </div>
</div>

<!-- Controls & Studio Presets -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-pink-400"><i class="bi bi-sliders me-2"></i>Dynamics Controls</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>THRESHOLD (dB)</span>
                        <span class="text-pink-400 fw-bold" id="threshLabel">-18 dB</span>
                    </label>
                    <input type="range" class="form-range" min="-40" max="0" value="-18" id="threshSlider" oninput="document.getElementById('threshLabel').innerText = this.value + ' dB'">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>RATIO</span>
                        <span class="text-amber-400 fw-bold" id="ratioLabel">4.0 : 1</span>
                    </label>
                    <input type="range" class="form-range" min="1" max="20" value="4" step="0.5" id="ratioSlider" oninput="document.getElementById('ratioLabel').innerText = this.value + ' : 1'">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>MAKEUP GAIN (dB)</span>
                        <span class="text-emerald-400 fw-bold" id="makeupLabel">+3.0 dB</span>
                    </label>
                    <input type="range" class="form-range" min="0" max="12" value="3" step="0.5" id="makeupSlider" oninput="document.getElementById('makeupLabel').innerText = '+' + this.value + ' dB'">
                </div>

                <button class="btn btn-sm btn-pink text-white fw-bold w-100" style="background: #EC4899;" onclick="processCompressionDemo()">
                    <i class="bi bi-play-circle-fill me-1"></i> Apply Dynamics Processing
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-mic-fill me-2"></i>Studio Presets</span>
                <span class="badge bg-secondary">VOCAL MASTER</span>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2 mb-3">
                    <button class="btn btn-sm btn-outline-light text-start" onclick="applyPreset('broadcast_voice', -18, 4, 3.5)">
                        <i class="bi bi-broadcast me-2 text-pink-400"></i><strong>Broadcast Voice</strong> &mdash; Warm, controlled dynamics (-18dB, 4:1)
                    </button>
                    <button class="btn btn-sm btn-outline-light text-start" onclick="applyPreset('punchy_podcast', -14, 6, 5.0)">
                        <i class="bi bi-megaphone me-2 text-amber-400"></i><strong>Punchy Podcast</strong> &mdash; In-your-face clarity (-14dB, 6:1)
                    </button>
                    <button class="btn btn-sm btn-outline-light text-start" onclick="applyPreset('vocal_leveler', -24, 2.5, 2.0)">
                        <i class="bi bi-music-note me-2 text-emerald-400"></i><strong>Vocal Leveler</strong> &mdash; Transparent smoothing (-24dB, 2.5:1)
                    </button>
                    <button class="btn btn-sm btn-outline-light text-start" onclick="applyPreset('brickwall_limiter', -2, 20, 0)">
                        <i class="bi bi-shield-fill me-2 text-info"></i><strong>Brickwall Limiter</strong> &mdash; Anti-clipping safety ceiling (-2dB, 20:1)
                    </button>
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="compressorResultBox">
                    [Ready] Apply a preset or adjust sliders to compress audio...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applyPreset(name, t, r, m) {
    document.getElementById('threshSlider').value = t;
    document.getElementById('threshLabel').innerText = t + ' dB';
    document.getElementById('ratioSlider').value = r;
    document.getElementById('ratioLabel').innerText = r + ' : 1';
    document.getElementById('makeupSlider').value = m;
    document.getElementById('makeupLabel').innerText = '+' + m + ' dB';
    processCompressionDemo();
}

async function processCompressionDemo() {
    const t = parseFloat(document.getElementById('threshSlider').value);
    const r = parseFloat(document.getElementById('ratioSlider').value);
    const m = parseFloat(document.getElementById('makeupSlider').value);

    const sampleFrames = [0.10, 0.35, 0.75, 0.98, 0.85, 0.40, -0.65, -0.95, -0.80, 0.20];

    try {
        const res = await apiFetch('/voice/compressor/process', {
            method: 'POST',
            body: JSON.stringify({
                audio_frames: sampleFrames,
                threshold_db: t,
                ratio: r,
                makeup_gain_db: m
            })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricGR').innerText = `-${d.max_gain_reduction_db} dB`;
            document.getElementById('metricPeakOut').innerText = `${d.peak_after_db} dBFS`;
            document.getElementById('metricRatio').innerText = `${d.ratio} : 1`;

            document.getElementById('compressorResultBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[PROCESSED] Peak In: ${d.peak_before_db} dB &rarr; Peak Out: ${d.peak_after_db} dB</div>
                <div class="text-white text-xs"><strong>Max Gain Reduction:</strong> -${d.max_gain_reduction_db} dB</div>
                <div class="text-muted text-xs">Samples: ${d.samples_processed} | Ceiling: &le; -0.1 dBFS</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Audio compressed (GR: -${d.max_gain_reduction_db} dB)`, 'success');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Compressor error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    processCompressionDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
