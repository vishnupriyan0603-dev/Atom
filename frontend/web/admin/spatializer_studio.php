<?php
// ATOM Web Admin — Phase 94: Real-Time Audio Spatializer & 3D Binaural HRTF Audio Panning Engine
$pageTitle = "3D Audio Spatializer & Binaural HRTF (Phase 94)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">3D Binaural Audio Spatializer &amp; HRTF</h2>
        <p class="text-muted small mb-0">Phase 94: Head-Related Transfer Function (HRTF), Interaural Time (ITD) &amp; Level (ILD) Differences, Inverse-Square Distance Rolloff</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="processSpatialDemo()">
            <i class="bi bi-soundwave me-1"></i> Spatialize Audio Buffer
        </button>
    </div>
</div>

<!-- Spatial Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AZIMUTH ANGLE</div>
            <div class="fs-4 fw-bold text-sky-400" id="metricAzimuth" style="color: #38BDF8;">45.0&deg; (Right)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ITD TIME DELAY</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricItd" style="color: #34D399;">0.537 ms</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DISTANCE GAIN</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricDistGain">0.816x (-1.7dB)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">HRTF PROCESSING</div>
            <div class="fs-4 fw-bold text-pink-400">Binaural Stereo</div>
        </div>
    </div>
</div>

<!-- Controls & Stereo Channel Inspector -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-sky-400"><i class="bi bi-compass me-2"></i>3D Spatial Coordinates</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>AZIMUTH ANGLE (&deg;)</span>
                        <span class="text-sky-400 fw-bold" id="azimuthLabel">45&deg;</span>
                    </label>
                    <input type="range" class="form-range" min="-180" max="180" value="45" id="azimuthSlider" oninput="document.getElementById('azimuthLabel').innerText = this.value + '&deg;'">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>DISTANCE (METERS)</span>
                        <span class="text-emerald-400 fw-bold" id="distanceLabel">1.5 m</span>
                    </label>
                    <input type="range" class="form-range" min="0.2" max="10" step="0.1" value="1.5" id="distanceSlider" oninput="document.getElementById('distanceLabel').innerText = this.value + ' m'">
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button class="btn btn-sm btn-outline-light text-start" onclick="applySpatialPreset('front_center', 0, 1.5)">
                        <i class="bi bi-mic me-2 text-sky-400"></i><strong>Front-Center Stage</strong> (0&deg;, 1.5m)
                    </button>
                    <button class="btn btn-sm btn-outline-light text-start" onclick="applySpatialPreset('left_ear_close', -90, 0.4)">
                        <i class="bi bi-ear me-2 text-pink-400"></i><strong>Left Ear Intimate Whisper</strong> (-90&deg;, 0.4m)
                    </button>
                    <button class="btn btn-sm btn-outline-light text-start" onclick="applySpatialPreset('cinematic_far_right', 60, 4.0)">
                        <i class="bi bi-film me-2 text-amber-400"></i><strong>Cinematic Far Right</strong> (+60&deg;, 4.0m)
                    </button>
                </div>

                <button class="btn btn-sm btn-info text-dark fw-bold w-100" onclick="processSpatialDemo()">
                    <i class="bi bi-play-circle-fill me-1"></i> Spatialize Audio
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-cyan-400"><i class="bi bi-headphones me-2"></i>Binaural Stereo Output</span>
                <span class="badge bg-secondary">STEREO PAIR</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="spatialResultBox">
                    [Ready] Click 'Spatialize Audio' to render binaural audio waveforms...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function applySpatialPreset(name, az, dist) {
    document.getElementById('azimuthSlider').value = az;
    document.getElementById('azimuthLabel').innerText = az + '\u00B0';
    document.getElementById('distanceSlider').value = dist;
    document.getElementById('distanceLabel').innerText = dist + ' m';
    processSpatialDemo();
}

async function processSpatialDemo() {
    const az = parseFloat(document.getElementById('azimuthSlider').value);
    const dist = parseFloat(document.getElementById('distanceSlider').value);

    const monoFrames = [0.10, 0.45, 0.85, 0.95, 0.60, -0.40, -0.90, -0.75, 0.15];

    try {
        const res = await apiFetch('/voice/spatial/process', {
            method: 'POST',
            body: JSON.stringify({
                mono_frames: monoFrames,
                azimuth_deg: az,
                distance_m: dist
            })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricAzimuth').innerText = `${d.azimuth_deg}\u00B0 (${d.azimuth_deg < 0 ? 'Left' : (d.azimuth_deg > 0 ? 'Right' : 'Center')})`;
            document.getElementById('metricItd').innerText = `${d.itd_delay_ms} ms`;
            document.getElementById('metricDistGain').innerText = `${d.distance_gain}x`;

            document.getElementById('spatialResultBox').innerHTML = `
                <div class="text-emerald-400 fw-bold mb-1">[SPATIALIZED] Azimuth: ${d.azimuth_deg}&deg; | Distance: ${d.distance_m}m</div>
                <div class="text-white text-xs mb-1"><strong>ILD Gains:</strong> Left: ${d.ild_left_gain}x | Right: ${d.ild_right_gain}x</div>
                <div class="text-muted text-xs mb-2"><strong>ITD Interaural Delay:</strong> ${d.itd_delay_ms} ms</div>
                <div class="font-monospace text-xs text-sky-400">Left (${d.left_channel.length}s): [${d.left_channel.slice(0, 5).join(', ')}...]</div>
                <div class="font-monospace text-xs text-pink-400">Right (${d.right_channel.length}s): [${d.right_channel.slice(0, 5).join(', ')}...]</div>
            `;

            if (typeof showToast === 'function') {
                showToast(`Audio spatialized at ${d.azimuth_deg}\u00B0`, 'success');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Spatial error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    processSpatialDemo();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
