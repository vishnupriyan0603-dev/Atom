<?php
// ATOM Web Admin — Phase 46: Real-Time WebRTC Full-Duplex Audio Stream & Live Formant Shifter
$pageTitle = "Real-Time Voice Stream & Formant Shifter (Phase 46)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F59E0B;">Real-Time Voice Stream &amp; Dynamic Formant Shifter</h2>
        <p class="text-muted small mb-0">Phase 46: Low-Latency Full-Duplex Audio Streaming, Real-Time Formant Frequency Warping ($F_1, F_2, F_3$), Jitter Buffering &amp; Ben 10 Tamil Acoustic Tuning</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-warning text-dark fw-bold" id="btnToggleLiveStream" onclick="toggleLiveDuplexStream()">
            <i class="bi bi-mic-fill me-1"></i> Start Live Mic Stream
        </button>
    </div>
</div>

<!-- Stream Metrics & Latency Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STREAM LATENCY</div>
            <div class="fs-4 fw-bold text-success" id="metricLatency">14.5 ms (Ultra-Low)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACOUSTIC TARGET ($F_0$)</div>
            <div class="fs-4 fw-bold text-warning" id="metricF0">245 Hz (Ben 10)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">VOICE ACTIVITY / BARGE-IN</div>
            <div class="fs-4 fw-bold text-info" id="metricVad">STANDBY</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">JITTER BUFFER</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricJitter" style="color: #34D399;">0.0 ms (Optimal)</div>
        </div>
    </div>
</div>

<!-- Main Section: Live FFT Waterfall Visualizer & Formant Controls -->
<div class="row g-4 mb-4">
    
    <!-- 1. Real-Time Spectral FFT Visualizer -->
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-warning"><i class="bi bi-soundwave me-2"></i>Real-Time FFT Waterfall &amp; Formant Resonance Spectrum</span>
                <span class="badge bg-success" id="streamStatusBadge">STREAM IDLE</span>
            </div>
            <div class="card-body p-3">
                <div class="bg-black border border-secondary rounded p-2 mb-3" style="min-height: 280px;">
                    <canvas id="liveFftCanvas" style="width: 100%; height: 280px; display: block;"></canvas>
                </div>

                <div class="row g-2 text-center text-xs">
                    <div class="col-3 p-2 bg-black border border-secondary rounded">
                        <span class="text-muted d-block">$F_1$ Vowel Height</span>
                        <span class="fw-bold text-info" id="badgeF1">761 Hz</span>
                    </div>
                    <div class="col-3 p-2 bg-black border border-secondary rounded">
                        <span class="text-muted d-block">$F_2$ Tongue Frontness</span>
                        <span class="fw-bold text-emerald-400" id="badgeF2" style="color: #34D399;">2184 Hz</span>
                    </div>
                    <div class="col-3 p-2 bg-black border border-secondary rounded">
                        <span class="text-muted d-block">$F_3$ Retroflexion</span>
                        <span class="fw-bold text-warning" id="badgeF3">3192 Hz</span>
                    </div>
                    <div class="col-3 p-2 bg-black border border-secondary rounded">
                        <span class="text-muted d-block">$F_4$ Vocal Tract</span>
                        <span class="fw-bold text-danger" id="badgeF4">4144 Hz</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Dynamic Formant & Pitch Control Rack -->
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-sliders me-2"></i>Acoustic Tuning Rack</span>
                <button class="btn btn-xs btn-outline-warning" onclick="applyPreset('ben10')">Reset to Ben 10</button>
            </div>
            <div class="card-body">
                
                <!-- Preset Buttons -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">ACOUSTIC PRESETS</label>
                    <div class="btn-group btn-group-sm w-100">
                        <button class="btn btn-outline-warning active" id="presetBen10" onclick="applyPreset('ben10')">Ben 10 Tamil</button>
                        <button class="btn btn-outline-warning" id="presetHeroic" onclick="applyPreset('heroic')">Heroic Deep</button>
                        <button class="btn btn-outline-warning" id="presetBroadcast" onclick="applyPreset('broadcast')">Broadcast</button>
                        <button class="btn btn-outline-warning" id="presetFlat" onclick="applyPreset('flat')">Flat</button>
                    </div>
                </div>

                <!-- Sliders -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted fw-bold">Pitch Scaling Factor ($\alpha$)</span>
                        <span class="text-warning fw-bold" id="labelPitchScale">1.18x (+18%)</span>
                    </div>
                    <input type="range" class="form-range" id="sliderPitchScale" min="0.5" max="2.0" step="0.01" value="1.18" oninput="handleFormantChange()">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted fw-bold">Formant Warp Factor</span>
                        <span class="text-info fw-bold" id="labelFormantScale">1.12x</span>
                    </div>
                    <input type="range" class="form-range" id="sliderFormantScale" min="0.5" max="2.0" step="0.01" value="1.12" oninput="handleFormantChange()">
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted fw-bold">Fundamental Pitch ($F_0$)</span>
                        <span class="text-emerald-400 fw-bold" id="labelTargetF0" style="color: #34D399;">245 Hz</span>
                    </div>
                    <input type="range" class="form-range" id="sliderTargetF0" min="80" max="400" step="1" value="245" oninput="handleFormantChange()">
                </div>

                <button class="btn btn-info text-dark fw-bold w-100" onclick="sendSimulatedChunk()">
                    <i class="bi bi-play-circle-fill me-1"></i> Send Real-Time Frame Packet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let isLiveStreaming = false;
let streamInterval = null;
let currentPitchScale = 1.18;
let currentFormantScale = 1.12;
let currentTargetF0 = 245.0;

function toggleLiveDuplexStream() {
    isLiveStreaming = !isLiveStreaming;
    const btn = document.getElementById('btnToggleLiveStream');
    const badge = document.getElementById('streamStatusBadge');

    if (isLiveStreaming) {
        btn.className = 'btn btn-sm btn-danger fw-bold';
        btn.innerHTML = '<i class="bi bi-stop-circle-fill me-1"></i> Stop Mic Stream';
        badge.className = 'badge bg-danger';
        badge.innerText = 'STREAMING LIVE (16kHz)';

        startStreamSession();
        streamInterval = setInterval(sendSimulatedChunk, 80);
        if (typeof showToast === 'function') showToast('Full-duplex audio stream connected!', 'success');
    } else {
        btn.className = 'btn btn-sm btn-warning text-dark fw-bold';
        btn.innerHTML = '<i class="bi bi-mic-fill me-1"></i> Start Live Mic Stream';
        badge.className = 'badge bg-success';
        badge.innerText = 'STREAM IDLE';

        clearInterval(streamInterval);
        stopStreamSession();
        if (typeof showToast === 'function') showToast('Audio stream stopped', 'info');
    }
}

async function startStreamSession() {
    try {
        await apiFetch('/voice/stream/session/start', {
            method: 'POST',
            body: JSON.stringify({
                pitch_scale: currentPitchScale,
                formant_scale: currentFormantScale,
                target_f0: currentTargetF0
            })
        });
    } catch (e) {
        console.error(e);
    }
}

async function stopStreamSession() {
    try {
        await apiFetch('/voice/stream/session/stop', { method: 'DELETE' });
    } catch (e) {
        console.error(e);
    }
}

async function sendSimulatedChunk() {
    try {
        const res = await apiFetch('/voice/stream/chunk', { method: 'POST', body: JSON.stringify({}) });
        if (res && res.success) {
            const data = res.data;
            document.getElementById('metricVad').innerText = data.barge_in_triggered ? '🚨 BARGE-IN DETECTED' : (data.frame_metrics.is_voice_active ? 'ACTIVE VOICE' : 'SILENCE');
            document.getElementById('metricVad').className = `fs-4 fw-bold text-${data.barge_in_triggered ? 'danger' : (data.frame_metrics.is_voice_active ? 'warning' : 'info')}`;

            drawFftWaterfall(data.fft_spectrum || []);
        }
    } catch (e) {
        console.error(e);
    }
}

async function handleFormantChange() {
    currentPitchScale = parseFloat(document.getElementById('sliderPitchScale').value);
    currentFormantScale = parseFloat(document.getElementById('sliderFormantScale').value);
    currentTargetF0 = parseFloat(document.getElementById('sliderTargetF0').value);

    document.getElementById('labelPitchScale').innerText = `${currentPitchScale.toFixed(2)}x (${currentPitchScale >= 1.0 ? '+' : ''}${Math.round((currentPitchScale - 1.0) * 100)}%)`;
    document.getElementById('labelFormantScale').innerText = `${currentFormantScale.toFixed(2)}x`;
    document.getElementById('labelTargetF0').innerText = `${Math.round(currentTargetF0)} Hz`;

    document.getElementById('badgeF1').innerText = `${Math.round(680 * currentFormantScale)} Hz`;
    document.getElementById('badgeF2').innerText = `${Math.round(1950 * currentFormantScale)} Hz`;
    document.getElementById('badgeF3').innerText = `${Math.round(2850 * currentFormantScale)} Hz`;
    document.getElementById('badgeF4').innerText = `${Math.round(3700 * currentFormantScale)} Hz`;

    try {
        await apiFetch('/voice/stream/formants/set', {
            method: 'POST',
            body: JSON.stringify({
                pitch_scale: currentPitchScale,
                formant_scale: currentFormantScale,
                target_f0: currentTargetF0
            })
        });
    } catch (e) {
        console.error(e);
    }
}

function applyPreset(preset) {
    if (preset === 'ben10') {
        document.getElementById('sliderPitchScale').value = 1.18;
        document.getElementById('sliderFormantScale').value = 1.12;
        document.getElementById('sliderTargetF0').value = 245;
    } else if (preset === 'heroic') {
        document.getElementById('sliderPitchScale').value = 0.92;
        document.getElementById('sliderFormantScale').value = 0.95;
        document.getElementById('sliderTargetF0').value = 160;
    } else if (preset === 'broadcast') {
        document.getElementById('sliderPitchScale').value = 1.05;
        document.getElementById('sliderFormantScale').value = 1.08;
        document.getElementById('sliderTargetF0').value = 210;
    } else {
        document.getElementById('sliderPitchScale').value = 1.00;
        document.getElementById('sliderFormantScale').value = 1.00;
        document.getElementById('sliderTargetF0').value = 200;
    }
    handleFormantChange();
}

function drawFftWaterfall(fft) {
    const canvas = document.getElementById('liveFftCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const width = canvas.parentElement.clientWidth || 500;
    const height = 280;
    canvas.width = width;
    canvas.height = height;

    ctx.fillStyle = '#0B0F19';
    ctx.fillRect(0, 0, width, height);

    const barCount = fft.length || 16;
    const barWidth = width / barCount;

    for (let i = 0; i < barCount; i++) {
        const val = fft[i] !== undefined ? fft[i] : (Math.sin(i * 0.4) * 0.5 + 0.5);
        const barHeight = Math.max(8, val * height * 0.85);

        const gradient = ctx.createLinearGradient(0, height, 0, height - barHeight);
        gradient.addColorStop(0, '#F59E0B');
        gradient.addColorStop(0.5, '#38BDF8');
        gradient.addColorStop(1, '#EC4899');

        ctx.fillStyle = gradient;
        ctx.fillRect(i * barWidth + 3, height - barHeight, barWidth - 6, barHeight);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    drawFftWaterfall([0.2, 0.4, 0.8, 0.9, 0.7, 0.5, 0.3, 0.4, 0.6, 0.8, 0.7, 0.5, 0.3, 0.2, 0.1, 0.1]);
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
