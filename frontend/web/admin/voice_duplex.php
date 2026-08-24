<?php
// ATOM Web Admin — Phase 34: Real-Time Voice Duplex & Continuous Streaming Audio Brain Dashboard
$pageTitle = "Voice Duplex & Streaming Audio";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">Autonomous Real-Time Voice Duplex Brain</h2>
        <p class="text-muted small mb-0">Full-duplex continuous audio streaming, wake-word gating, conversational turn-taking with barge-in &amp; prosodic emotion classification</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-dark fw-bold" style="background: linear-gradient(135deg, #06B6D4 0%, #0891B2 100%); border: none;" onclick="startDuplexSession()">
            <i class="bi bi-mic-fill me-1"></i> Start Duplex Session
        </button>
    </div>
</div>

<!-- Voice Duplex Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STREAM PROTOCOL</div>
            <div class="fs-4 fw-bold text-info" id="metricStream">PCM 16kHz (Active)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">WAKE DETECTOR</div>
            <div class="fs-4 fw-bold text-success" id="metricWake">"Hey Atom"</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CONVERSATIONAL TURN</div>
            <div class="fs-4 fw-bold text-warning" id="metricTurn">IDLE (0 Turns)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">EMOTION PROFILE</div>
            <div class="fs-4 fw-bold" style="color:#06B6D4;" id="metricEmotion">NEUTRAL</div>
        </div>
    </div>
</div>

<!-- Voice Streaming & Barge-in Controls Grid -->
<div class="row g-4 mb-4">
    <!-- 1. Real-Time Audio Streaming Visualizer & Chunk Feeder -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-soundwave me-2"></i>Live Audio Waveform &amp; Chunk Feeder</span>
                <span class="badge bg-info text-dark" id="streamBadge">STREAM READY</span>
            </div>
            <div class="card-body">
                <!-- Canvas Waveform Visualizer -->
                <div class="mb-3 p-2 bg-black border border-secondary rounded text-center">
                    <canvas id="waveformCanvas" width="450" height="70" style="width: 100%; max-height: 70px;"></canvas>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SIMULATED SPEECH INPUT</label>
                    <div class="input-group">
                        <input type="text" id="chunkTextInput" class="form-control bg-black text-white border-secondary" value="Hey Atom, check the database status" placeholder="Spoken phrase to stream...">
                        <button class="btn btn-info text-dark fw-bold" onclick="sendAudioChunk()">Stream Chunk</button>
                    </div>
                </div>
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-sm btn-danger w-100 fw-bold" onclick="triggerBargeIn()">
                        <i class="bi bi-stop-circle-fill me-1"></i> Barge-In Interruption Signal
                    </button>
                </div>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 110px;">
                    <div class="text-muted small fw-bold mb-1">STREAM TELEMETRY:</div>
                    <div id="chunkOutput" class="small text-info" style="font-family: monospace; white-space: pre-wrap;">
Duplex stream idle. Click 'Stream Chunk' to simulate voice packets.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Prosodic Audio Emotion Analyzer -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#06B6D4;"><i class="bi bi-heart-pulse me-2"></i>Prosodic Audio Emotion Classifier</span>
                <span class="badge bg-secondary" id="emotionBadge">NEUTRAL</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold mb-0">PITCH (Hz): <span id="valPitch" class="text-info">160</span></label>
                        <input type="range" class="form-range" id="sliderPitch" min="80" max="320" value="160" oninput="updateEmotionLive()">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold mb-0">ENERGY (dB): <span id="valEnergy" class="text-info">-20</span></label>
                        <input type="range" class="form-range" id="sliderEnergy" min="-40" max="0" value="-20" oninput="updateEmotionLive()">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold mb-0">RATE (WPM): <span id="valRate" class="text-info">140</span></label>
                        <input type="range" class="form-range" id="sliderRate" min="80" max="220" value="140" oninput="updateEmotionLive()">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold mb-0">VARIANCE: <span id="valVar" class="text-info">25</span></label>
                        <input type="range" class="form-range" id="sliderVar" min="5" max="80" value="25" oninput="updateEmotionLive()">
                    </div>
                </div>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 140px;">
                    <div class="text-muted small fw-bold mb-1">EMOTION CLASSIFICATION &amp; TONE ADAPTATION:</div>
                    <div id="emotionOutput" class="small text-emerald-400" style="font-family: monospace; white-space: pre-wrap; color:#34D399;">
Adjust acoustic sliders to classify emotional state.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let sequence = 0;
let isAnimating = true;

// Animated Waveform Canvas
function drawWaveform() {
    const canvas = document.getElementById('waveformCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;

    ctx.clearRect(0, 0, width, height);
    ctx.strokeStyle = '#06B6D4';
    ctx.lineWidth = 2;
    ctx.beginPath();

    const time = Date.now() / 150;
    for (let x = 0; x < width; x++) {
        const y = height / 2 + Math.sin(x * 0.05 + time) * 15 * Math.cos(x * 0.02 + time * 0.5);
        if (x === 0) ctx.moveTo(x, y);
        else ctx.lineTo(x, y);
    }
    ctx.stroke();

    if (isAnimating) requestAnimationFrame(drawWaveform);
}
drawWaveform();

async function startDuplexSession() {
    try {
        const res = await fetch('/api/v1/voice/duplex/start', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({})
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('metricTurn').innerText = `${data.data.state} (0 Turns)`;
            document.getElementById('streamBadge').innerText = 'SESSION ACTIVE';
            document.getElementById('chunkOutput').innerText = `Session started: ${data.data.session_id}\nProtocol: ${data.data.protocol}`;
        }
    } catch (e) {
        document.getElementById('chunkOutput').innerText = 'Error: ' + e.message;
    }
}

async function sendAudioChunk() {
    sequence++;
    const text = document.getElementById('chunkTextInput').value;
    try {
        const res = await fetch('/api/v1/voice/duplex/chunk', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                type: 'CHUNK',
                sequence: sequence,
                payload: btoa(text),
                text: text,
                vad_active: true
            })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('metricTurn').innerText = `${data.data.current_state} (${data.data.turn_count} Turns)`;
            document.getElementById('chunkOutput').innerText = JSON.stringify(data.data, null, 2);
            if (data.data.wake_detected) {
                document.getElementById('metricWake').innerText = `DETECTED: "${data.data.wake_phrase}"`;
            }
        }
    } catch (e) {
        document.getElementById('chunkOutput').innerText = 'Chunk error: ' + e.message;
    }
}

async function triggerBargeIn() {
    try {
        const res = await fetch('/api/v1/voice/duplex/interrupt', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({})
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('metricTurn').innerText = `INTERRUPTED -> IDLE`;
            document.getElementById('chunkOutput').innerText = `Barge-In Interruption triggered successfully!\nSpeech immediately halted.`;
        }
    } catch (e) {
        document.getElementById('chunkOutput').innerText = 'Interrupt error: ' + e.message;
    }
}

async function updateEmotionLive() {
    const pitch = parseFloat(document.getElementById('sliderPitch').value);
    const energy = parseFloat(document.getElementById('sliderEnergy').value);
    const rate = parseFloat(document.getElementById('sliderRate').value);
    const variance = parseFloat(document.getElementById('sliderVar').value);

    document.getElementById('valPitch').innerText = pitch;
    document.getElementById('valEnergy').innerText = energy;
    document.getElementById('valRate').innerText = rate;
    document.getElementById('valVar').innerText = variance;

    try {
        const res = await fetch('/api/v1/voice/duplex/emotion', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                pitch_hz: pitch,
                energy_db: energy,
                speech_rate_wpm: rate,
                pitch_variance: variance
            })
        });
        const data = await res.json();
        if (data.success) {
            const d = data.data;
            document.getElementById('metricEmotion').innerText = d.emotion.toUpperCase();
            document.getElementById('emotionBadge').innerText = d.emotion.toUpperCase();
            document.getElementById('emotionOutput').innerText = 
                `CLASSIFIED EMOTION : ${d.emotion.toUpperCase()} (Confidence: ${(d.confidence * 100).toFixed(1)}%)\n` +
                `RECOMMENDED TONE   : ${d.adaptation.recommended_tone}\n` +
                `SPEECH RATE MOD    : ${d.adaptation.speech_rate_mod}x\n` +
                `PITCH MODULATION   : ${d.adaptation.pitch_mod}x`;
        }
    } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => updateEmotionLive());
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
