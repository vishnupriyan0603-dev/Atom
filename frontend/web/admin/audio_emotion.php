<?php
// ATOM Web Admin — Phase 68: Real-Time Audio Emotion & Acoustic Mood Classifier
$pageTitle = "Voice Emotion Classifier (Phase 68)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F59E0B;">Audio Emotion &amp; Acoustic Mood Classifier</h2>
        <p class="text-muted small mb-0">Phase 68: Ben 10 Voice Prosodic Intent, Omnitrix Battle / Tactical / Calm Mood Classifier &amp; SSML Rate/Pitch Modifiers</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-dark fw-bold" style="background: #F59E0B;" onclick="classifyAudioEmotion()">
            <i class="bi bi-soundwave me-1"></i> Classify Intent
        </button>
    </div>
</div>

<!-- Mood Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PRIMARY ACOUSTIC MOOD</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricMood" style="color: #F59E0B;">HEROIC / BATTLE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CLASSIFICATION CONFIDENCE</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricConfidence" style="color: #34D399;">91.0% MATCH</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SSML PITCH SHIFT</div>
            <div class="fs-4 fw-bold text-info" id="metricPitch">+15% PITCH</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SSML CADENCE RATE</div>
            <div class="fs-4 fw-bold text-pink-400" id="metricRate" style="color: #EC4899;">+10% RATE</div>
        </div>
    </div>
</div>

<!-- Emotion Testing Sandbox -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-amber-400"><i class="bi bi-mic-fill me-2"></i>Test Speech Intent / Acoustic Features</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SAMPLE TEXT INTENT</label>
                    <input type="text" id="sampleTextInput" class="form-control bg-black text-white border-secondary small" value="It's Hero Time! Omnitrix transform into Heatblast!" placeholder="Enter speech phrase...">
                </div>

                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-xs btn-outline-warning" onclick="setPreset('Omnitrix battle power attack!')">🔥 Heroic Preset</button>
                    <button class="btn btn-xs btn-outline-info" onclick="setPreset('Initiating core platform diagnostic scans.')">⚙️ Analytical Preset</button>
                    <button class="btn btn-xs btn-outline-success" onclick="setPreset('Everything is safe and calm now.')">🍃 Calm Preset</button>
                </div>

                <button class="btn btn-sm text-dark fw-bold w-100" style="background: #F59E0B;" onclick="classifyAudioEmotion()">
                    <i class="bi bi-soundwave me-1"></i> Classify Speech Emotion
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-diagram-3-fill me-2"></i>Synthesized SSML Prosody Tag</span>
                <span class="badge bg-warning text-dark" id="moodBadge">HEROIC_BATTLE</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary mb-3">
                    <div class="text-xs text-muted mb-1 font-monospace">Generated SSML Tag:</div>
                    <code class="text-emerald-400 fs-6 font-monospace" id="ssmlCodeTag" style="color: #34D399;">&lt;prosody pitch="+15%" rate="+10%"&gt;...&lt;/prosody&gt;</code>
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted">
                    <i class="bi bi-info-circle text-amber-400 me-1"></i> Dynamically feeds into Phase 43 Tamil TTS Engine &amp; Phase 62 Autotune Harmonizer for real-time voice adaptation.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setPreset(txt) {
    document.getElementById('sampleTextInput').value = txt;
    classifyAudioEmotion();
}

async function classifyAudioEmotion() {
    const text = document.getElementById('sampleTextInput').value;

    try {
        const res = await apiFetch('/voice/emotion/classify', {
            method: 'POST',
            body: JSON.stringify({ text: text })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricMood').innerText = d.mood_label;
            document.getElementById('metricConfidence').innerText = `${(d.confidence * 100).toFixed(1)}% MATCH`;
            document.getElementById('metricPitch').innerText = `${d.ssml_modifiers.pitch} PITCH`;
            document.getElementById('metricRate').innerText = `${d.ssml_modifiers.rate} RATE`;
            document.getElementById('moodBadge').innerText = d.primary_mood;
            document.getElementById('ssmlCodeTag').innerText = `<prosody pitch="${d.ssml_modifiers.pitch}" rate="${d.ssml_modifiers.rate}">...</prosody>`;

            if (typeof showToast === 'function') showToast(`Acoustic Mood: ${d.mood_label}`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Emotion error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    classifyAudioEmotion();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
