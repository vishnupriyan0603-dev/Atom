<?php
// ATOM Web Admin — Phase 62: Real-Time Audio Pitch Correction & Autotune Voice Harmonizer
$pageTitle = "Voice Harmonizer (Phase 62)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F43F5E;">Real-Time Audio Pitch Correction &amp; Voice Harmonizer</h2>
        <p class="text-muted small mb-0">Phase 62: Autocorrelation Pitch Tracker, Autotune Scale Quantizer &amp; Multi-Part Vocal Harmonizer (Heroic Alien Octave &amp; Formants)</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-danger text-white fw-bold" style="background: #F43F5E;" onclick="runAutotuneCorrection()">
            <i class="bi bi-mic-fill me-1"></i> Autotune &amp; Harmonize
        </button>
    </div>
</div>

<!-- Harmonizer Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">RAW INPUT PITCH</div>
            <div class="fs-4 fw-bold text-danger" id="metricRawPitch">248.5 Hz (B3 +24c)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">QUANTIZED TARGET</div>
            <div class="fs-4 fw-bold text-success" id="metricTargetPitch">246.9 Hz (B3 0c)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DETUNE CORRECTION</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricCents" style="color: #34D399;">-11.2 Cents (Locked)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE VOICES</div>
            <div class="fs-4 fw-bold text-pink-400" id="metricVoices">4-Part Harmony</div>
        </div>
    </div>
</div>

<!-- Harmonizer Mixer & Scale Configuration -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-rose-400"><i class="bi bi-sliders me-2"></i>Pitch Correction Controls</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>INPUT FREQUENCY (F0)</span>
                        <span id="freqValText" class="text-rose-400 fw-bold">248.5 Hz</span>
                    </label>
                    <input type="range" class="form-range" id="freqRange" min="100" max="600" step="0.5" value="248.5" oninput="document.getElementById('freqValText').innerText = this.value + ' Hz'; runAutotuneCorrection();">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">AUTOTUNE SCALE</label>
                    <select class="form-select bg-black text-white border-secondary small" id="scaleSelect" onchange="runAutotuneCorrection()">
                        <option value="c_major">C Major (Natural Diatonic)</option>
                        <option value="a_minor">A Minor (Heroic Melodic)</option>
                        <option value="alien_heroic_245" selected>Ben 10 Alien Heroic Resonance (245 Hz)</option>
                    </select>
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted mb-3">
                    <i class="bi bi-info-circle text-rose-400 me-1"></i> Phase 62 autocorrelation quantizes raw vocal pitches to precise musical frequencies, locking Tamil speech into heroic formants.
                </div>

                <button class="btn btn-sm text-white fw-bold w-100" style="background: #F43F5E;" onclick="runAutotuneCorrection()">
                    <i class="bi bi-music-note-beamed me-1"></i> Lock &amp; Synthesize Harmony
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-pink-400"><i class="bi bi-speaker-fill me-2"></i>Multi-Voice Harmony Channel Mixer</span>
                <span class="badge bg-danger">LIVE MIX</span>
            </div>
            <div class="card-body p-3" id="harmoniesContainer">
                <div class="text-muted small">Loading vocal harmony channels...</div>
            </div>
        </div>
    </div>
</div>

<script>
async function runAutotuneCorrection() {
    const freq = parseFloat(document.getElementById('freqRange').value);
    const scale = document.getElementById('scaleSelect').value;

    try {
        const resPitch = await apiFetch('/voice/harmonizer/correct-pitch', {
            method: 'POST',
            body: JSON.stringify({ frequency_hz: freq, scale: scale })
        });

        const resHarmonies = await apiFetch('/voice/harmonizer/generate-harmonies', {
            method: 'POST',
            body: JSON.stringify({ base_frequency_hz: freq })
        });

        if (resPitch && resPitch.success) {
            const data = resPitch.data;
            document.getElementById('metricRawPitch').innerText = `${data.original_freq_hz} Hz`;
            document.getElementById('metricTargetPitch').innerText = `${data.target_freq_hz} Hz (MIDI ${data.midi_note})`;
            document.getElementById('metricCents').innerText = `${data.detune_cents} Cents (${data.is_in_tune ? 'In Tune' : 'Correcting'})`;
        }

        if (resHarmonies && resHarmonies.success) {
            const voices = resHarmonies.data.voices || [];
            document.getElementById('metricVoices').innerText = `${voices.length}-Part Harmony`;
            renderHarmonies(voices);
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Harmonizer error: ' + e.message, 'error');
    }
}

function renderHarmonies(voices) {
    const container = document.getElementById('harmoniesContainer');
    if (!voices || voices.length === 0) return;

    container.innerHTML = voices.map((v, idx) => {
        const colors = ['#F43F5E', '#EC4899', '#38BDF8', '#34D399'];
        const col = colors[idx % colors.length];

        return `
            <div class="p-2.5 rounded bg-black border border-secondary mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-xs" style="color: ${col};">${escapeHtml(v.label)}</span>
                    <span class="text-white text-xs fw-bold">${v.frequency_hz} Hz</span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" style="width: ${v.gain * 100}%; background: ${col};"></div>
                </div>
            </div>
        `;
    }).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    runAutotuneCorrection();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
