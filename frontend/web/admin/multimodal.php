<?php
// ATOM Web Admin — Phase 24: Multi-Modal Speech, Voice & Vision Dashboard
$pageTitle = "Multi-Modal Voice & Vision Engine";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Multi-Modal Voice &amp; Vision Engine</h2>
        <p class="text-muted small mb-0">Real-time Speech Synthesis (TTS), Voice Input Transcription (STT), and Screenshot/Image Intelligence</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm" style="background: linear-gradient(135deg, #EC4899 0%, #8B5CF6 100%); border: none; color: white;" onclick="testVoiceSynthesis()">
            <i class="bi bi-volume-up me-1"></i> Quick Voice Test
        </button>
    </div>
</div>

<!-- Multi-Modal Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TTS ENGINE</div>
            <div class="fs-4 fw-bold" style="color:#EC4899;">ACTIVE (Web/SSML)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AVAILABLE VOICES</div>
            <div class="fs-4 fw-bold text-info" id="metricVoicesCount">4 PRESETS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">VISION ENGINE</div>
            <div class="fs-4 fw-bold text-success">MULTI-MODAL</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">STT TRANSCRIBER</div>
            <div class="fs-4 fw-bold text-warning">READY</div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Panel 1: Speech Synthesis (TTS) Testbench -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#EC4899;"><i class="bi bi-soundwave me-2"></i>Speech Synthesis (TTS) Testbench</span>
                <span class="badge bg-pink text-white" style="background:#EC4899;">LIVE AUDIO</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Text to Synthesize</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="ttsInputText" rows="3" placeholder="Hello Vichu! Atom Multi-Modal Speech Engine is operational."></textarea>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Voice Preset</label>
                        <select class="form-select bg-dark text-white border-secondary" id="ttsVoiceSelect">
                            <option value="en-US-Neural2-F">ATOM Female (US)</option>
                            <option value="en-US-Neural2-D">ATOM Male (US)</option>
                            <option value="en-IN-Standard-A" selected>ATOM Indian English (Female)</option>
                            <option value="en-IN-Standard-B">ATOM Indian English (Male)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Speech Rate (<span id="rateLabel">1.0</span>x)</label>
                        <input type="range" class="form-range" id="ttsRate" min="0.5" max="2.0" step="0.1" value="1.0" oninput="document.getElementById('rateLabel').textContent = this.value">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-fill" style="background: linear-gradient(135deg, #EC4899 0%, #8B5CF6 100%); border:none;" onclick="synthesizeSpeech()">
                        <i class="bi bi-play-fill me-1"></i> Speak Audio
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="stopSpeech()">
                        <i class="bi bi-stop-fill me-1"></i> Stop
                    </button>
                </div>
                <div id="ttsStatus" class="mt-3 text-muted small"></div>
            </div>
        </div>
    </div>

    <!-- Panel 2: Voice Input & Transcription (STT) -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#8B5CF6;"><i class="bi bi-mic me-2"></i>Voice Input &amp; Transcription (STT)</span>
                <span class="badge bg-secondary">SPEECH-TO-TEXT</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Record voice input via microphone or simulate audio buffer transmission for real-time transcription.</p>
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-outline-danger btn-sm" id="btnRecordMic" onclick="toggleMicrophone()">
                        <i class="bi bi-record-circle me-1"></i> <span id="recordLabel">Start Recording</span>
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="testSimulatedTranscription()">
                        <i class="bi bi-file-earmark-music me-1"></i> Test Sample Audio
                    </button>
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted small fw-bold">Transcribed Output</label>
                    <div class="p-3 bg-black border border-secondary rounded" id="sttOutputText" style="min-height: 100px; color: #E2E8F0; font-family: monospace;">
                        (Transcription will appear here...)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 3: Vision & Screenshot Inspector -->
    <div class="col-12">
        <div class="card bg-dark border-secondary text-white">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#10B981;"><i class="bi bi-eye me-2"></i>Vision &amp; Screenshot Intelligence</span>
                <span class="badge bg-success">MULTI-MODAL</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Task Type</label>
                            <select class="form-select bg-dark text-white border-secondary" id="visionTaskType">
                                <option value="screenshot_debug" selected>Screenshot Debugging (Error &amp; Stacktrace)</option>
                                <option value="ui_to_code">UI Mockup to Code (HTML / Bootstrap)</option>
                                <option value="diagram_parse">Architecture Diagram Parsing</option>
                                <option value="general_analysis">General Multi-Modal Analysis</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Upload Image / Screenshot</label>
                            <input type="file" class="form-control bg-dark text-white border-secondary" id="visionFileInput" accept="image/*" onchange="previewVisionImage(event)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Custom Prompt (Optional)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="visionCustomPrompt" placeholder="e.g. Find the SQL syntax error in this screenshot">
                        </div>
                        <button class="btn btn-success btn-sm w-100" onclick="analyzeVisionImage()">
                            <i class="bi bi-cpu me-1"></i> Analyze Image
                        </button>
                        <div class="mt-3 text-center" id="imagePreviewContainer" style="display:none;">
                            <img id="imagePreview" src="" alt="Preview" class="img-fluid rounded border border-secondary" style="max-height: 180px;">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label text-muted small fw-bold">Analysis &amp; Diagnostic Result</label>
                        <div class="p-3 bg-black border border-secondary rounded" id="visionResultArea" style="min-height: 280px; color: #34D399; font-family: monospace; white-space: pre-wrap;">
Upload or select an image to inspect multi-modal analysis output.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const API_BASE = window.ATOM_API_BASE || '/api';
const TOKEN    = localStorage.getItem('atom_token') || '';
let selectedImageBase64 = '';
let selectedImageMime = 'image/png';
let isRecording = false;
let recognition = null;

function apiFetch(path, opts = {}) {
    return fetch(API_BASE + path, {
        ...opts,
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + TOKEN, ...(opts.headers || {}) }
    }).then(r => r.json());
}

function synthesizeSpeech() {
    const text = document.getElementById('ttsInputText').value.trim() || 'Hello Vichu! Atom Speech Engine is active.';
    const voice = document.getElementById('ttsVoiceSelect').value;
    const rate = parseFloat(document.getElementById('ttsRate').value);

    // Try browser Web Speech API first
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = rate;
        window.speechSynthesis.speak(utterance);
        document.getElementById('ttsStatus').innerHTML = '<span class="text-success"><i class="bi bi-volume-up"></i> Playing audio via Web Speech API...</span>';
    }

    // Call backend synthesis API
    apiFetch('/voice/synthesize', {
        method: 'POST',
        body: JSON.stringify({ text: text, voice: voice })
    }).then(res => {
        if (res.success) {
            document.getElementById('ttsStatus').innerHTML += ' <span class="text-muted">(SSML & Meta validated)</span>';
        }
    }).catch(() => {});
}

function stopSpeech() {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        document.getElementById('ttsStatus').innerHTML = '<span class="text-warning">Audio stopped.</span>';
    }
}

function testVoiceSynthesis() {
    document.getElementById('ttsInputText').value = 'Greetings Vichu! Multi-modal voice and vision capabilities are fully operational.';
    synthesizeSpeech();
}

function toggleMicrophone() {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        alert('Web Speech API is not supported in this browser. You can still use the sample audio test.');
        return;
    }

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!recognition) {
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.onresult = function(event) {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                transcript += event.results[i][0].transcript;
            }
            document.getElementById('sttOutputText').textContent = transcript;
        };
        recognition.onerror = function() {
            isRecording = false;
            document.getElementById('recordLabel').textContent = 'Start Recording';
        };
    }

    if (!isRecording) {
        recognition.start();
        isRecording = true;
        document.getElementById('recordLabel').textContent = 'Stop Recording';
        document.getElementById('sttOutputText').textContent = 'Listening... Speak now.';
    } else {
        recognition.stop();
        isRecording = false;
        document.getElementById('recordLabel').textContent = 'Start Recording';
    }
}

function testSimulatedTranscription() {
    const dummyAudioBase64 = 'UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA=';
    apiFetch('/voice/transcribe', {
        method: 'POST',
        body: JSON.stringify({ audio_data: dummyAudioBase64, language: 'en' })
    }).then(res => {
        if (res.success) {
            document.getElementById('sttOutputText').textContent = res.data.text;
        } else {
            document.getElementById('sttOutputText').textContent = 'Transcription error: ' + (res.error || 'Failed');
        }
    }).catch(err => {
        document.getElementById('sttOutputText').textContent = 'API Error: ' + err.message;
    });
}

function previewVisionImage(event) {
    const file = event.target.files[0];
    if (!file) return;

    selectedImageMime = file.type || 'image/png';
    const reader = new FileReader();
    reader.onload = function(e) {
        const result = e.target.result;
        selectedImageBase64 = result.split(',')[1] || result;
        document.getElementById('imagePreview').src = result;
        document.getElementById('imagePreviewContainer').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function analyzeVisionImage() {
    if (!selectedImageBase64) {
        // Use a 1x1 dummy pixel if no file selected for testing
        selectedImageBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    const taskType = document.getElementById('visionTaskType').value;
    const prompt = document.getElementById('visionCustomPrompt').value;
    const resultArea = document.getElementById('visionResultArea');
    resultArea.textContent = 'Processing multi-modal vision inference...';

    apiFetch('/vision/analyze', {
        method: 'POST',
        body: JSON.stringify({
            image_base64: selectedImageBase64,
            mime_type: selectedImageMime,
            task_type: taskType,
            prompt: prompt
        })
    }).then(res => {
        if (res.success) {
            const d = res.data;
            resultArea.textContent = `[Task: ${d.task_type} | Size: ${d.size_bytes} bytes]\n\n` + (d.data?.analysis || JSON.stringify(d, null, 2));
        } else {
            resultArea.textContent = 'Vision error: ' + (res.error || 'Analysis failed');
        }
    }).catch(err => {
        resultArea.textContent = 'API Error: ' + err.message;
    });
}
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
