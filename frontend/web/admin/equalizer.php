<?php
// ATOM Web Admin — DSP Audio Equalizer Studio & Visualizer
$pageTitle = "DSP Audio Equalizer Studio";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #10B981;">Parametric 10-Band Audio Equalizer</h2>
        <p class="text-muted small mb-0">DSP Filter Chain, Web Audio API biquad nodes, live frequency response curves, and acoustic speech enhancement presets</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); border: none;" onclick="saveEqualizerProfile()">
            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Sync DSP Profile
        </button>
    </div>
</div>

<!-- Equalizer Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DSP FILTER ENGINE</div>
            <div class="fs-4 fw-bold text-success" id="metricEqStatus">ONLINE (10-Band)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE PRESET</div>
            <div class="fs-4 fw-bold text-info" id="metricActivePreset">FLAT (0 dB)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SAMPLE RATE</div>
            <div class="fs-4 fw-bold text-warning">48.0 kHz (Hi-Fi)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PREAMP GAIN</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricPreampVal">+0.0 dB</div>
        </div>
    </div>
</div>

<!-- Main Equalizer Component Mount -->
<div class="row mb-4">
    <div class="col-12">
        <div id="equalizerContainer"></div>
    </div>
</div>

<!-- Equalizer Presets & Acoustic Analysis Strip -->
<div class="row g-4 mb-4">
    <!-- Preset Gallery -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-sliders2-vertical me-2"></i>Curated Acoustic Presets</span>
                <span class="badge bg-secondary">10 PROFILES</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Click any preset to instantly tune the 10-band DSP filter chain for specific speech or listening scenarios:</p>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('SPEECH_CLARITY')"><i class="bi bi-mic me-1"></i> Speech Clarity</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('VOCAL_ENHANCE')"><i class="bi bi-person-lines-fill me-1"></i> Vocal Enhance</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('NOISE_REDUCTION')"><i class="bi bi-shield-shaded me-1"></i> Noise Reduction</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('PODCAST')"><i class="bi bi-broadcast me-1"></i> Podcast</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('BASS_BOOST')"><i class="bi bi-soundwave me-1"></i> Bass Boost</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('TREBLE_BOOST')"><i class="bi bi-activity me-1"></i> Treble Boost</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('ACOUSTIC')"><i class="bi bi-music-note me-1"></i> Acoustic</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('ELECTRONIC')"><i class="bi bi-disc me-1"></i> Electronic</button>
                    <button class="btn btn-sm btn-outline-info" onclick="window.eqInstance.applyPreset('ROCK')"><i class="bi bi-lightning me-1"></i> Rock</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.eqInstance.applyPreset('FLAT')"><i class="bi bi-arrow-counterclockwise me-1"></i> Flat (Reset)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DSP Telemetry Log -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-terminal-fill me-2"></i>DSP Filter Chain Telemetry</span>
                <span class="badge bg-success text-dark">SYNCHRONIZED</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black border border-secondary rounded font-monospace small" id="eqTelemetryLog" style="height: 140px; overflow-y: auto; color: #34D399; font-size: 11px;">
Equalizer DSP engine initialized. Ready to process voice streaming packets.
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/static/js/equalizer.js"></script>
<script>
let eqInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    eqInstance = new ProductionEqualizer({
        containerId: 'equalizerContainer',
        canvasId: 'equalizerCanvas',
        onStateChange: (state) => {
            document.getElementById('metricActivePreset').textContent = state.preset.replace('_', ' ');
            document.getElementById('metricPreampVal').textContent = `${state.preamp >= 0 ? '+' : ''}${state.preamp.toFixed(1)} dB`;
            document.getElementById('metricEqStatus').textContent = state.enabled ? 'ONLINE (10-Band)' : 'BYPASSED';
            document.getElementById('metricEqStatus').className = state.enabled ? 'fs-4 fw-bold text-success' : 'fs-4 fw-bold text-danger';

            const log = document.getElementById('eqTelemetryLog');
            if (log) {
                const bandsSummary = state.bands.map(b => `${b >= 0 ? '+' : ''}${b}`).join(', ');
                log.innerHTML = `[${new Date().toLocaleTimeString()}] STATE: ${state.enabled ? 'ENABLED' : 'BYPASS'} | Preset: ${state.preset} | Preamp: ${state.preamp}dB\n` +
                                `BANDS (32Hz..16kHz): [${bandsSummary}]\n` +
                                `LOW-CUT: ${state.lowCut ? 'ON (80Hz)' : 'OFF'} | HIGH-CUT: ${state.highCut ? 'ON (12kHz)' : 'OFF'}\n` +
                                log.innerHTML;
            }
        }
    });

    eqInstance.init();
    window.eqInstance = eqInstance;
});

async function saveEqualizerProfile() {
    if (!eqInstance) return;
    try {
        const res = await fetch('/api/v1/voice/equalizer/apply', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(eqInstance.state)
        });
        const data = await res.json();
        if (data.success) {
            alert('Equalizer profile synchronized to Atom Brain DSP successfully!');
        }
    } catch (e) {
        console.error('Error syncing EQ:', e);
    }
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
