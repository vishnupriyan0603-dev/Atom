<?php
// ATOM Web Admin — Phase 69: Natural Conversational Dialogue Orchestrator & Persona Studio
$pageTitle = "Natural Dialogue Studio (Phase 69)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #60A5FA;">Natural Conversational Dialogue &amp; Persona Studio</h2>
        <p class="text-muted small mb-0">Phase 69: Warm Human-Like Greetings, Multi-Tone Emotion Adaptation, Gentle English Learning &amp; 3-Tier Teaching Architecture</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-primary text-white fw-bold" onclick="testDialogueTurn()">
            <i class="bi bi-chat-dots-fill me-1"></i> Test Dialogue Turn
        </button>
    </div>
</div>

<!-- Core Persona Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DETECTED EMOTIONAL TONE</div>
            <div class="fs-4 fw-bold text-blue-400" id="metricTone">NEUTRAL / WARM</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CONVERSATIONAL GREETING</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricGreeting" style="color: #34D399;">NATURAL (NON-ROBOTIC)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ENGLISH LEARNING HELPER</div>
            <div class="fs-4 fw-bold text-amber-400" id="metricEnglish" style="color: #F59E0B;">ACTIVE &amp; GENTLE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TEACHING ARCHITECTURE</div>
            <div class="fs-4 fw-bold text-cyan-400">3-Tier (Concept/Example/Tip)</div>
        </div>
    </div>
</div>

<!-- Dialogue Sandbox & Tone Adaptation Playground -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-blue-400"><i class="bi bi-chat-heart-fill me-2"></i>Test Natural User Turn</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">USER MESSAGE</label>
                    <input type="text" id="dialogueInput" class="form-control bg-black text-white border-secondary small" value="hey, good morning! I am use two provider in my system." placeholder="Enter user prompt...">
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button class="btn btn-xs btn-outline-info" onclick="setSample('hey, good morning!')">👋 Casual Greeting</button>
                    <button class="btn btn-xs btn-outline-warning" onclick="setSample('I am really frustrated because the database query failed!')">😤 Frustrated Tone</button>
                    <button class="btn btn-xs btn-outline-danger" onclick="setSample('I am use two provider')">📝 English Learning Cue</button>
                    <button class="btn btn-xs btn-outline-success" onclick="setSample('How does database indexing work?')">🎓 Teaching Question</button>
                </div>

                <button class="btn btn-sm btn-primary text-white fw-bold w-100" onclick="testDialogueTurn()">
                    <i class="bi bi-send-fill me-1"></i> Process Conversational Turn
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-person-check-fill me-2"></i>Assistant Response &amp; Guidance</span>
                <span class="badge bg-primary" id="toneBadge">NEUTRAL</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary mb-3">
                    <div class="text-xs text-muted mb-1 font-monospace">Assistant Natural Output:</div>
                    <div class="text-white small" id="assistantResponseText">Processing message...</div>
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted" id="englishTipBox" style="display: none;">
                    <div class="fw-bold text-amber-400 mb-1"><i class="bi bi-lightbulb-fill me-1"></i>Gentle English Tip:</div>
                    <div class="text-muted" id="englishTipContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setSample(txt) {
    document.getElementById('dialogueInput').value = txt;
    testDialogueTurn();
}

async function testDialogueTurn() {
    const msg = document.getElementById('dialogueInput').value;

    try {
        const res = await apiFetch('/brain/dialogue/respond', {
            method: 'POST',
            body: JSON.stringify({ message: msg })
        });

        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricTone').innerText = d.detected_tone.toUpperCase();
            document.getElementById('toneBadge').innerText = d.detected_tone.toUpperCase();
            document.getElementById('assistantResponseText').innerText = d.response;

            const tipBox = document.getElementById('englishTipBox');
            if (d.english_tip) {
                tipBox.style.display = 'block';
                document.getElementById('englishTipContent').innerText = `${d.english_tip.explanation} (Suggestion: "${d.english_tip.suggestion}")`;
            } else {
                tipBox.style.display = 'none';
            }

            if (typeof showToast === 'function') showToast(`Tone: ${d.detected_tone}`, 'success');
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Dialogue error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    testDialogueTurn();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
