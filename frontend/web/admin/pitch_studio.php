<?php
// ATOM Web Admin — Phase 78: Autonomous Real-Time Audio Pitch Corrector & Dynamic Harmonizer Engine
$pageTitle = "Pitch Harmonizer (Phase 78)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F59E0B;">Real-Time Audio Pitch Corrector &amp; Harmonizer</h2>
        <p class="text-muted small mb-0">Phase 78: Autocorrelation Pitch Tracking, Scale Auto-Tuning (Major, Minor, Tamil Kalyani Raga) &amp; Multi-Voice Vocal Chords</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-warning text-dark fw-bold" onclick="processPitchCorrection()">
            <i class="bi bi-music-note-beamed me-1"></i> Auto-Tune Vocals
        </button>
    </div>
</div>

<!-- Pitch Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE SCALE</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricScale" style="color: #F59E0B;">TAMIL KALYANI</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TARGET SEMITONE</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricNote" style="color: #34D399;">Note 4 (E)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PITCH SHIFT AMOUNT</div>
            <div class="fs-4 fw-bold text-info" id="metricShift">+0.8 st</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HARMONY VOICES</div>
            <div class="fs-4 fw-bold text-pink-400" id="metricVoices">3 VOICES</div>
        </div>
    </div>
</div>

<!-- Tuning & Harmonizer Controls -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-amber-400"><i class="bi bi-sliders me-2"></i>Scale &amp; Correction Speed</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SELECT MUSICAL SCALE</label>
                    <select id="scaleSelect" class="form-select bg-black text-white border-secondary small" onchange="processPitchCorrection()">
                        <option value="tamil_kalyani" selected>Tamil 65th Melakartha (Kalyani Raga)</option>
                        <option value="major">Major Scale (Natural)</option>
                        <option value="minor">Minor Scale (Melancholic)</option>
                        <option value="pentatonic">Pentatonic Scale (Blues/Rock)</option>
                        <option value="chromatic">Chromatic Scale (All 12 Notes)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>CORRECTION SPEED</span>
                        <span class="text-amber-400 fw-bold" id="speedLabel">80% (Robotic Auto-Tune)</span>
                    </label>
                    <input type="range" class="form-range" min="0" max="100" value="80" id="speedSlider" oninput="updateSpeedLabel(this.value)">
                </div>

                <button class="btn btn-sm btn-warning text-dark fw-bold w-100" onclick="processPitchCorrection()">
                    <i class="bi bi-play-circle-fill me-1"></i> Apply Real-Time Correction
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-soundwave me-2"></i>Multi-Voice Harmony Synthesizer</span>
                <span class="badge bg-success" id="harmonyBadge">ACTIVE CHORDS</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary mb-3 text-xs text-muted">
                    <div class="text-white fw-bold mb-1">Synthesizing 3 Parallel Harmony Layers:</div>
                    <div>1. <strong>Lead Dry Vocal:</strong> 0 semitones (Center)</div>
                    <div>2. <strong>Major Third:</strong> +4 semitones (Left Channel)</div>
                    <div>3. <strong>Perfect Fifth:</strong> +7 semitones (Right Channel)</div>
                </div>

                <button class="btn btn-sm btn-outline-info text-white fw-bold w-100" onclick="synthesizeHarmoniesDemo()">
                    <i class="bi bi-mic-fill me-1"></i> Synthesize 3-Voice Harmonized Vocal
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function updateSpeedLabel(v) {
    document.getElementById('speedLabel').innerText = `${v}% (${v > 70 ? 'Robotic Auto-Tune' : 'Natural Transparent'})`;
}

async function processPitchCorrection() {
    const scale = document.getElementById('scaleSelect').value;
    const speed = parseFloat(document.getElementById('speedSlider').value) / 100.0;

    try {
        const res = await apiFetch('/voice/pitch/autotune', {
            method: 'POST',
            body: JSON.stringify({
                audio_frames: [0.1, 0.4, 0.7, 0.3, -0.2, -0.6, 0.1, 0.5, 0.9, -0.4],
                scale: scale,
                speed: speed
            })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricScale').innerText = d.scale.toUpperCase();
            document.getElementById('metricNote').innerText = `Note ${d.target_semitone}`;
            document.getElementById('metricShift').innerText = `${d.pitch_shift_semitones > 0 ? '+' : ''}${d.pitch_shift_semitones} st`;

            if (typeof showToast === 'function') showToast(`Pitch corrected to ${d.scale} (${d.pitch_shift_semitones} st)`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Pitch error: ' + e.message, 'error');
    }
}

async function synthesizeHarmoniesDemo() {
    try {
        const res = await apiFetch('/voice/pitch/harmonize', {
            method: 'POST',
            body: JSON.stringify({
                audio_frames: [0.2, 0.5, 0.8, -0.3, -0.5, 0.4, 0.7],
                intervals: [4, 7]
            })
        });

        if (res && res.success) {
            document.getElementById('metricVoices').innerText = `${res.data.voices_count} VOICES`;
            if (typeof showToast === 'function') showToast(`Harmonies synthesized (${res.data.voices_count} voices)`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Harmony error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    processPitchCorrection();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
