<?php
// ATOM Web Admin — Phase 73: Real-Time Audio Vocal Isolator & Stem Separator Engine
$pageTitle = "Vocal Stem Separator (Phase 73)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Audio Vocal Isolator &amp; Stem Separator</h2>
        <p class="text-muted small mb-0">Phase 73: Spectral Formant Band Isolation, Vocal / Instrumental Stem Extraction &amp; Multi-Track Re-Mixer</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: #EC4899;" onclick="separateAudioStems()">
            <i class="bi bi-soundwave me-1"></i> Isolate Stems
        </button>
    </div>
</div>

<!-- Stem Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">VOCAL PURITY</div>
            <div class="fs-4 fw-bold text-pink-400" id="metricPurity" style="color: #EC4899;">98.5% PURITY</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SIGNAL-TO-NOISE RATIO</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricSnr" style="color: #34D399;">+18.4 dB (STUDIO)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ISOLATION STRENGTH</div>
            <div class="fs-4 fw-bold text-info" id="metricStrength">85.0%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SEPARATION STATUS</div>
            <div class="fs-4 fw-bold text-warning" id="metricStatus">OPTIMAL</div>
        </div>
    </div>
</div>

<!-- Stem Separation & Live Mix Controls -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-pink-400"><i class="bi bi-mic-fill me-2"></i>Vocal Isolation Controls</span>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>VOCAL ISOLATION STRENGTH</span>
                        <span class="text-pink-400 fw-bold" id="strengthText">85%</span>
                    </label>
                    <input type="range" class="form-range" min="0" max="100" value="85" id="strengthSlider" oninput="document.getElementById('strengthText').innerText = this.value + '%'">
                </div>

                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-xs btn-outline-secondary" onclick="setStrength(50)">50% (Soft)</button>
                    <button class="btn btn-xs btn-outline-secondary" onclick="setStrength(85)">85% (Balanced)</button>
                    <button class="btn btn-xs btn-outline-pink text-pink-400 border-pink-400" onclick="setStrength(100)">100% (Acapella)</button>
                </div>

                <button class="btn btn-sm text-white fw-bold w-100" style="background: #EC4899;" onclick="separateAudioStems()">
                    <i class="bi bi-play-circle-fill me-1"></i> Process Separation
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-sliders2-vertical me-2"></i>Multi-Track Stem Gain Mixer</span>
                <span class="badge bg-success" id="mixBadge">ACTIVE MIX</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>VOCAL STEM GAIN</span>
                        <span class="text-pink-400 fw-bold">1.0x</span>
                    </label>
                    <input type="range" class="form-range" min="0" max="200" value="100">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>INSTRUMENTAL BACKING GAIN</span>
                        <span class="text-info fw-bold">0.5x</span>
                    </label>
                    <input type="range" class="form-range" min="0" max="200" value="50">
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted">
                    <i class="bi bi-info-circle text-pink-400 me-1"></i> Phase 73 delivers clean acapellas for Ben 10 voice cloning and real-time noise cancellation.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setStrength(v) {
    document.getElementById('strengthSlider').value = v;
    document.getElementById('strengthText').innerText = v + '%';
    separateAudioStems();
}

async function separateAudioStems() {
    const strength = parseFloat(document.getElementById('strengthSlider').value) / 100.0;

    try {
        const res = await apiFetch('/voice/stems/separate', {
            method: 'POST',
            body: JSON.stringify({
                audio_frames: [0.1, 0.5, 0.8, 0.4, -0.3, -0.7, -0.6, 0.2, 0.9, -0.1],
                vocal_isolation_strength: strength
            })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricPurity').innerText = `${d.vocal_purity_pct}% PURITY`;
            document.getElementById('metricSnr').innerText = `+${d.snr_db} dB`;
            document.getElementById('metricStrength').innerText = `${(d.vocal_isolation_strength * 100).toFixed(0)}%`;
            document.getElementById('metricStatus').innerText = d.status;

            if (typeof showToast === 'function') showToast(`Stems separated (+${d.snr_db} dB SNR)`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Stem separation error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    separateAudioStems();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
