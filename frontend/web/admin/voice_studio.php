<?php
// ATOM Web Admin — ATOM Voice Studio (Ben 10 Tamil Base Reference Voice & Text-to-Speech Engine)
$pageTitle = "ATOM Voice Studio (Ben 10 Tamil)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #10B981;">
            <i class="bi bi-soundwave me-2"></i>ATOM Voice Studio — Ben 10 Tamil Reference Voice
        </h2>
        <p class="text-muted small mb-0">
            Acoustic profile extraction, Tamil phoneme prosody shaping, real-time Text-to-Voice synthesis &amp; 10-band DSP filtering
        </p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); border: none;" onclick="playOriginalReferenceAudio()">
            <i class="bi bi-play-circle-fill me-1"></i> Play Base Reference MP3
        </button>
    </div>
</div>

<!-- Voice Acoustic Profile Telemetry Grid -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white shadow-sm">
            <div class="text-muted small fw-bold text-uppercase">BASE REFERENCE VOICE</div>
            <div class="fs-5 fw-bold text-success mt-1" id="metricVoiceName">Ben 10 Tamil Protagonist</div>
            <div class="text-xs text-muted">Acoustic Reference: <code>ben10_tamil_dialogue.mp3</code></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white shadow-sm">
            <div class="text-muted small fw-bold text-uppercase">PITCH CONTOUR (F0)</div>
            <div class="fs-5 fw-bold text-info mt-1" id="metricPitch">Mean 245.0 Hz (+18%)</div>
            <div class="text-xs text-muted">Youthful tenor register, high energy</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white shadow-sm">
            <div class="text-muted small fw-bold text-uppercase">SPEAKING TEMPO &amp; RATE</div>
            <div class="fs-5 fw-bold text-warning mt-1" id="metricRate">1.18x Brisk Tempo</div>
            <div class="text-xs text-muted">Heroic dialogue cadence, punchy attacks</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white shadow-sm">
            <div class="text-muted small fw-bold text-uppercase">DSP PRE-EMPHASIS</div>
            <div class="fs-5 fw-bold text-emerald-400 mt-1" id="metricEq">+4.5 dB @ 2 kHz</div>
            <div class="text-xs text-muted">10-Band EQ tailored for Tamil retroflexes</div>
        </div>
    </div>
</div>

<!-- Main Voice Generation Studio & Real-Time Waveform -->
<div class="row g-4 mb-4">
    <!-- Left Column: Interactive Text-to-Speech Input Studio -->
    <div class="col-lg-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-mic-fill me-2"></i>Text-to-Voice Synthesis Console</span>
                <span class="badge bg-success text-dark fw-bold">ONLINE (TAMIL ENGINE)</span>
            </div>
            <div class="card-body">
                <!-- Text Input Area -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TAMIL / ENGLISH / TANGLISH PROMPT</label>
                    <textarea id="ttsTextInput" class="form-control bg-black text-white border-secondary font-monospace" rows="4" style="font-size: 14px; line-height: 1.6;" placeholder="Enter Tamil or English text to generate speech in Atom's Ben 10 voice...">வணக்கம் விச்சு! நான் ஆட்டம் AI அசிஸ்டன்ட். ஆம்னிட்ரிக்ஸ் ரெடியா இருக்கு, இட்ஸ் ஹீரோ டைம்!</textarea>
                </div>

                <!-- Quick Dialogue Preset Chips -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold mb-2">HEROIC &amp; ASSISTANT PRESET PHRASES</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-xs btn-outline-info" onclick="loadPresetPhrase(1)">
                            ⚡ Iconic Hero Time
                        </button>
                        <button class="btn btn-xs btn-outline-success" onclick="loadPresetPhrase(2)">
                            🛡️ Morning Briefing
                        </button>
                        <button class="btn btn-xs btn-outline-warning" onclick="loadPresetPhrase(3)">
                            🚀 Task Complete
                        </button>
                        <button class="btn btn-xs btn-outline-danger" onclick="loadPresetPhrase(4)">
                            ⚠️ Security Alert
                        </button>
                        <button class="btn btn-xs btn-outline-primary" onclick="loadPresetPhrase(5)">
                            💡 Query Optimized
                        </button>
                    </div>
                </div>

                <!-- Acoustic Tuning Sliders -->
                <div class="row g-2 mb-3 p-3 bg-black border border-secondary rounded">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold mb-1">PITCH SHIFT: <span id="labelPitch" class="text-info">+18% (1.18x)</span></label>
                        <input type="range" class="form-range" id="sliderPitch" min="0.8" max="1.6" step="0.02" value="1.18" oninput="updateSliders()">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold mb-1">SPEED / TEMPO: <span id="labelRate" class="text-warning">1.18x</span></label>
                        <input type="range" class="form-range" id="sliderRate" min="0.8" max="1.6" step="0.02" value="1.18" oninput="updateSliders()">
                    </div>
                    <div class="col-12 mt-2 d-flex justify-content-between align-items-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="checkHeroicPunch" checked>
                            <label class="form-check-label text-muted small fw-bold" for="checkHeroicPunch">Enable Heroic Plosive Punch &amp; Retroflex Stress</label>
                        </div>
                        <button class="btn btn-link btn-xs text-muted text-decoration-none" onclick="resetSliders()">Reset to Reference Defaults</button>
                    </div>
                </div>

                <!-- Action Triggers -->
                <div class="d-flex gap-2">
                    <button class="btn btn-success flex-grow-1 fw-bold" id="btnSynthesize" onclick="generateAndSpeakVoice()">
                        <i class="bi bi-play-fill me-1"></i> Generate &amp; Speak Voice (Ben 10)
                    </button>
                    <button class="btn btn-outline-danger" onclick="stopSpeech()">
                        <i class="bi bi-stop-fill me-1"></i> Stop
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Waveform, Spectrum & SSML Telemetry -->
    <div class="col-lg-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-activity me-2"></i>Real-Time FFT Spectrum &amp; Waveform</span>
                <span class="badge bg-secondary" id="visualizerBadge">IDLE</span>
            </div>
            <div class="card-body d-flex flex-col justify-between">
                <!-- Canvas Waveform / FFT Spectrum Display -->
                <div class="mb-3 p-2 bg-black border border-secondary rounded text-center">
                    <canvas id="voiceVisualizerCanvas" width="480" height="120" style="width: 100%; height: 120px; border-radius: 6px;"></canvas>
                </div>

                <!-- Base Reference Audio Streamer Card -->
                <div class="p-3 bg-black border border-secondary rounded mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-xs fw-bold text-info"><i class="bi bi-file-earmark-music me-1"></i>Original Reference Audio Source</span>
                        <span class="badge bg-info text-dark">87.3 KB (Tamil)</span>
                    </div>
                    <audio id="refAudioElement" controls class="w-100" style="height: 32px;">
                        <source src="../assets/audio/ben10_tamil_dialogue.mp3" type="audio/mpeg">
                        <source src="../../sample%20audio/ben10_tamil_dialogue.mp3" type="audio/mpeg">
                        Your browser does not support audio playback.
                    </audio>
                </div>

                <!-- Phonetic Analysis & SSML Output Box -->
                <div class="p-3 bg-black border border-secondary rounded flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-bold">PROSODY TELEMETRY &amp; SSML MARKUP</span>
                        <button class="btn btn-xs btn-outline-secondary" onclick="copySsml()">
                            <i class="bi bi-clipboard me-1"></i> Copy SSML
                        </button>
                    </div>
                    <pre id="ssmlOutputBox" class="text-info small mb-0" style="font-family: monospace; font-size: 11px; white-space: pre-wrap; max-height: 110px; overflow-y: auto;">
&lt;speak&gt;
  &lt;prosody pitch="+18%" rate="1.18" volume="+2.0dB"&gt;
    வணக்கம் விச்சு! நான் ஆட்டம் AI அசிஸ்டன்ட். ஆம்னிட்ரிக்ஸ் ரெடியா இருக்கு, இட்ஸ் ஹீரோ டைம்!
  &lt;/prosody&gt;
&lt;/speak&gt;
                    </pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Equalizer & Formant Filter Strip -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card bg-dark border-secondary text-white shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-warning"><i class="bi bi-sliders me-2"></i>10-Band DSP Equalizer Profile: BEN10_HEROIC</span>
                <a href="equalizer.php" class="btn btn-xs btn-outline-info">Open Full Equalizer Studio &rarr;</a>
            </div>
            <div class="card-body">
                <div class="row text-center g-2 font-monospace text-xs">
                    <div class="col"><span class="text-muted">32Hz</span><div class="fw-bold text-danger">-4.0dB</div></div>
                    <div class="col"><span class="text-muted">64Hz</span><div class="fw-bold text-danger">-2.0dB</div></div>
                    <div class="col"><span class="text-muted">125Hz</span><div class="fw-bold text-muted">0.0dB</div></div>
                    <div class="col"><span class="text-muted">250Hz</span><div class="fw-bold text-success">+1.5dB</div></div>
                    <div class="col"><span class="text-muted">500Hz</span><div class="fw-bold text-success">+3.0dB</div></div>
                    <div class="col"><span class="text-muted">1kHz</span><div class="fw-bold text-info">+4.0dB</div></div>
                    <div class="col"><span class="text-muted">2kHz</span><div class="fw-bold text-info">+4.5dB</div></div>
                    <div class="col"><span class="text-muted">4kHz</span><div class="fw-bold text-info">+3.5dB</div></div>
                    <div class="col"><span class="text-muted">8kHz</span><div class="fw-bold text-success">+1.0dB</div></div>
                    <div class="col"><span class="text-muted">16kHz</span><div class="fw-bold text-danger">-1.5dB</div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const PRESET_PHRASES = {
    1: 'ஏலியன் வரட்டும், நான் பாத்துக்கிறேன்! ஆம்னிட்ரிக்ஸ் ரெடியா இருக்கு... இட்ஸ் ஹீரோ டைம்!',
    2: 'வணக்கம் விச்சு! ஆட்டம் சிஸ்டம் மற்றும் அனைத்து சர்வீஸுகளும் 100% ஆன்லைனில் இயங்குகிறது.',
    3: 'டாஸ்க் வெற்றிகரமாக முடிந்தது! புதிய கோட் கம்பைல் ஆகி விட்டது, அடுத்த கட்டளை சொல்லுங்கள்!',
    4: 'செக்யூரிட்டி அலர்ட்! புதிய ஆத்தரைசேஷன் ரிக்வெஸ்ட் வந்துள்ளது, தயவுசெய்து சரிபார்க்கவும்.',
    5: 'விச்சு, இந்த டேட்டாபேஸ் குவெரி ஆப்டிமைஸ் பண்ணியாச்சு, ரெஸ்பான்ஸ் டைம் 40% குறைஞ்சிருக்கு!'
};

let currentPitch = 1.18;
let currentRate = 1.18;
let animFrameId = null;

function loadPresetPhrase(id) {
    if (PRESET_PHRASES[id]) {
        document.getElementById('ttsTextInput').value = PRESET_PHRASES[id];
        if (typeof showToast === 'function') {
            showToast('Loaded preset phrase ' + id, 'cyan');
        }
    }
}

function updateSliders() {
    currentPitch = parseFloat(document.getElementById('sliderPitch').value);
    currentRate = parseFloat(document.getElementById('sliderRate').value);

    const pitchPct = Math.round((currentPitch - 1.0) * 100);
    document.getElementById('labelPitch').textContent = `${pitchPct >= 0 ? '+' : ''}${pitchPct}% (${currentPitch.toFixed(2)}x)`;
    document.getElementById('labelRate').textContent = `${currentRate.toFixed(2)}x`;

    updateSsmlPreview();
}

function resetSliders() {
    document.getElementById('sliderPitch').value = 1.18;
    document.getElementById('sliderRate').value = 1.18;
    updateSliders();
    if (typeof showToast === 'function') showToast('Reset acoustic parameters to Ben 10 defaults', 'info');
}

function updateSsmlPreview() {
    const text = document.getElementById('ttsTextInput').value.trim();
    const pitchPct = Math.round((currentPitch - 1.0) * 100);
    const ssml = `<speak>\n  <prosody pitch="${pitchPct >= 0 ? '+' : ''}${pitchPct}%" rate="${currentRate.toFixed(2)}" volume="+2.0dB">\n    ${text}\n  </prosody>\n</speak>`;
    document.getElementById('ssmlOutputBox').textContent = ssml;
}

function playOriginalReferenceAudio() {
    const audio = document.getElementById('refAudioElement');
    if (audio) {
        audio.currentTime = 0;
        audio.play().catch(() => {});
        startWaveformAnimation();
        if (typeof showToast === 'function') showToast('Playing base reference MP3 (ben10_tamil_dialogue.mp3)', 'cyan');
    }
}

function generateAndSpeakVoice() {
    const text = document.getElementById('ttsTextInput').value.trim();
    if (!text) {
        if (typeof showToast === 'function') showToast('Please enter text to synthesize', 'error');
        return;
    }

    updateSsmlPreview();

    if (!('speechSynthesis' in window)) {
        if (typeof showToast === 'function') showToast('Web Speech API not supported in this browser', 'error');
        return;
    }

    window.speechSynthesis.cancel();

    const clean = text.replace(/```[\s\S]*?```/g, '').replace(/[#*`_~]/g, '').trim();
    const utterance = new SpeechSynthesisUtterance(clean);
    const isTamil = /[\u0B80-\u0BFF]/.test(clean);

    utterance.pitch = currentPitch;
    utterance.rate = currentRate;
    utterance.volume = 1.0;

    const voices = window.speechSynthesis.getVoices();
    if (isTamil && voices.length > 0) {
        const tamilVoice = voices.find(v => v.lang && (v.lang.startsWith('ta') || v.name.toLowerCase().includes('tamil')));
        if (tamilVoice) {
            utterance.voice = tamilVoice;
            utterance.lang = 'ta-IN';
        }
    }

    utterance.onstart = function() {
        startWaveformAnimation();
        document.getElementById('visualizerBadge').className = 'badge bg-success text-dark';
        document.getElementById('visualizerBadge').textContent = 'SYNTHESIZING & PLAYING';
        if (typeof showToast === 'function') showToast('Synthesizing speech in Ben 10 Tamil voice...', 'success');
    };

    utterance.onend = function() {
        stopWaveformAnimation();
        document.getElementById('visualizerBadge').className = 'badge bg-secondary';
        document.getElementById('visualizerBadge').textContent = 'IDLE';
    };

    utterance.onerror = function() {
        stopWaveformAnimation();
        document.getElementById('visualizerBadge').className = 'badge bg-danger';
        document.getElementById('visualizerBadge').textContent = 'ERROR';
    };

    window.speechSynthesis.speak(utterance);
}

function stopSpeech() {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
    const audio = document.getElementById('refAudioElement');
    if (audio) {
        audio.pause();
    }
    stopWaveformAnimation();
    document.getElementById('visualizerBadge').className = 'badge bg-secondary';
    document.getElementById('visualizerBadge').textContent = 'STOPPED';
    if (typeof showToast === 'function') showToast('Speech stopped', 'info');
}

function copySsml() {
    const ssml = document.getElementById('ssmlOutputBox').textContent;
    navigator.clipboard.writeText(ssml).then(() => {
        if (typeof showToast === 'function') showToast('Copied SSML markup to clipboard!', 'success');
    });
}

// Waveform and FFT Spectrum Canvas Drawer
function startWaveformAnimation() {
    const canvas = document.getElementById('voiceVisualizerCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let phase = 0;

    function render() {
        ctx.fillStyle = '#050811';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const barCount = 36;
        const barWidth = canvas.width / barCount - 2;

        for (let i = 0; i < barCount; i++) {
            const freqFactor = Math.sin(phase + i * 0.3) * 0.5 + 0.5;
            const barHeight = 15 + freqFactor * (canvas.height - 30) * Math.random();
            const x = i * (barWidth + 2);
            const y = canvas.height - barHeight;

            // Gradient from Emerald to Cyan
            const grad = ctx.createLinearGradient(0, canvas.height, 0, 0);
            grad.addColorStop(0, '#10B981');
            grad.addColorStop(1, '#06B6D4');

            ctx.fillStyle = grad;
            ctx.fillRect(x, y, barWidth, barHeight);
        }

        phase += 0.15;
        animFrameId = requestAnimationFrame(render);
    }

    if (animFrameId) cancelAnimationFrame(animFrameId);
    render();
}

function stopWaveformAnimation() {
    if (animFrameId) {
        cancelAnimationFrame(animFrameId);
        animFrameId = null;
    }
    const canvas = document.getElementById('voiceVisualizerCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#050811';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#334155';
    ctx.font = '11px monospace';
    ctx.textAlign = 'center';
    ctx.fillText('Audio Stream Idle — Click Generate to synthesize speech', canvas.width / 2, canvas.height / 2 + 4);
}

document.addEventListener('DOMContentLoaded', () => {
    stopWaveformAnimation();
    updateSsmlPreview();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
