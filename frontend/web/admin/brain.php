<?php
// ATOM Web Admin — Phase 23: Personal AI Brain Dashboard
$pageTitle = "Personal AI Brain — JARVIS Dashboard";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #A78BFA;">Personal AI Brain</h2>
        <p class="text-muted small mb-0">JARVIS-Style orchestration core — personality, context, awareness, voice &amp; intent</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-outline-warning btn-sm me-2" onclick="resetContext()">
            <i class="bi bi-trash me-1"></i> Reset Context
        </button>
        <button class="btn btn-sm" style="background: linear-gradient(135deg, #A78BFA 0%, #7C3AED 100%); border: none; color: white;" data-bs-toggle="modal" data-bs-target="#intentModal">
            <i class="bi bi-cpu me-1"></i> Intent Inspector
        </button>
    </div>
</div>

<!-- Brain Status Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">BRAIN STATE</div>
            <div class="fs-4 fw-bold" style="color:#A78BFA;" id="metricBrainState">—</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">PERSONALITY STYLE</div>
            <div class="fs-4 fw-bold text-info" id="metricStyle">—</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DEVICE CONTEXT</div>
            <div class="fs-4 fw-bold text-success" id="metricDevice">—</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">VOICE MODE</div>
            <div class="fs-4 fw-bold text-warning" id="metricVoice">—</div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Panel 1: Awareness Block -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#A78BFA;"><i class="bi bi-radar me-2"></i>Environment Awareness</span>
                <span class="badge bg-secondary">LIVE</span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-dark table-borderless mb-0" id="awarenessTable">
                    <tbody>
                        <tr><td class="text-muted">Time (IST)</td><td id="awTime">—</td></tr>
                        <tr><td class="text-muted">Day</td><td id="awDay">—</td></tr>
                        <tr><td class="text-muted">Time of Day</td><td id="awTod">—</td></tr>
                        <tr><td class="text-muted">Device</td><td id="awDevice">—</td></tr>
                        <tr><td class="text-muted">PHP Version</td><td id="awPhp">—</td></tr>
                        <tr><td class="text-muted">Workspace Files</td><td id="awFiles">—</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel 2: Active Context Thread -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#34D399;"><i class="bi bi-diagram-3 me-2"></i>Active Context Thread</span>
                <button class="btn btn-outline-danger btn-sm" onclick="resetContext()"><i class="bi bi-trash"></i></button>
            </div>
            <div class="card-body">
                <table class="table table-sm table-dark table-borderless mb-0">
                    <tbody>
                        <tr><td class="text-muted">Active Topic</td><td id="ctxTopic">—</td></tr>
                        <tr><td class="text-muted">Inferred Goal</td><td id="ctxGoal">—</td></tr>
                        <tr><td class="text-muted">Turn Count</td><td id="ctxTurns">—</td></tr>
                        <tr>
                            <td class="text-muted">Referenced Items</td>
                            <td id="ctxEntities">—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel 3: Personality Config -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold" style="color:#FBBF24;"><i class="bi bi-person-badge me-2"></i>Atom Personality &amp; Communication Style</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Atom's personality is a <strong>stateless style layer</strong> — it post-processes every reply for the owner's communication preferences.</p>
                <div class="mb-3">
                    <label class="text-muted small fw-bold d-block mb-1">STYLE</label>
                    <div id="styleTag" class="badge fs-6 text-dark" style="background: linear-gradient(135deg, #FBBF24, #F59E0B);">Loading…</div>
                </div>
                <div class="mb-3">
                    <label class="text-muted small fw-bold d-block mb-1">VOICE MODE</label>
                    <div id="voiceTag" class="badge fs-6">Loading…</div>
                </div>
                <div class="alert alert-dark border-secondary small mb-0">
                    <i class="bi bi-shield-lock me-1"></i>
                    <strong>Identity Rule:</strong> Atom never claims to be human. Personality is a communication style layer only.
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 4: Voice Mode Toggle -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold" style="color:#60A5FA;"><i class="bi bi-mic me-2"></i>Voice Engine</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Voice mode strips markdown symbols so Atom's responses are audio-friendly.
                    Full TTS synthesis (Google TTS / Web Speech API) is scheduled for a future phase.
                </p>
                <div class="mb-3">
                    <label class="text-muted small fw-bold d-block mb-2">CURRENT MODE</label>
                    <div id="voiceModeStatus" class="fs-5 fw-bold">—</div>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary" onclick="alert('Use /brain:voice on or /brain:voice off in the CLI to toggle voice mode.')">
                        <i class="bi bi-terminal me-2"></i>Toggle via CLI: <code>/brain:voice on|off</code>
                    </button>
                </div>
                <div class="mt-3 alert alert-dark border-secondary small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Phase 23 provides the text formatter. Synthesizer will be added in a future phase.
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Intent Inspector Modal -->
<div class="modal fade" id="intentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" style="color:#A78BFA;"><i class="bi bi-cpu me-2"></i>Intent Inspector</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Paste any user input to see how the Phase 23 IntentEngine classifies it.</p>
                <div class="mb-3">
                    <label class="form-label text-muted">Input Text</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="intentInputText" rows="3" placeholder="e.g. fix my login bug in login.php"></textarea>
                </div>
                <button class="btn btn-sm" style="background: linear-gradient(135deg, #A78BFA, #7C3AED); border:none; color:white;" onclick="classifyIntent()">
                    <i class="bi bi-lightning me-1"></i> Classify Intent
                </button>
                <div id="intentResult" class="mt-3" style="display:none;">
                    <div class="card bg-black border-secondary p-3">
                        <table class="table table-sm table-dark table-borderless mb-0">
                            <tbody>
                                <tr><td class="text-muted">Intent</td><td id="irIntent" class="fw-bold text-warning">—</td></tr>
                                <tr><td class="text-muted">Confidence</td><td id="irConfidence">—</td></tr>
                                <tr><td class="text-muted">Routing Hint</td><td id="irRouting">—</td></tr>
                                <tr><td class="text-muted">Entities</td><td id="irEntities">—</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const API_BASE = window.ATOM_API_BASE || '/api';
const TOKEN    = localStorage.getItem('atom_token') || '';

function apiFetch(path, opts = {}) {
    return fetch(API_BASE + path, {
        ...opts,
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + TOKEN, ...(opts.headers || {}) }
    }).then(r => r.json());
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val ?? '—';
}

function loadBrainStatus() {
    apiFetch('/brain/status').then(res => {
        if (!res.success) return;
        const d = res.data;
        setText('metricBrainState', d.brain_state?.toUpperCase() ?? 'IDLE');
        setText('metricStyle', d.personality_style?.toUpperCase() ?? '—');
        setText('metricDevice', d.device?.toUpperCase() ?? '—');
        setText('metricVoice', d.voice_mode ? 'ON' : 'OFF');

        // Awareness
        const env = d.environment ?? {};
        setText('awTime',   env.time_ist   ?? '—');
        setText('awDay',    env.day_of_week ?? '—');
        setText('awTod',    env.time_of_day ?? '—');
        setText('awDevice', env.device      ?? '—');
        setText('awPhp',    env.php_version ?? '—');
        setText('awFiles',  env.file_count  ?? '—');

        // Personality
        const styleTag = document.getElementById('styleTag');
        if (styleTag) styleTag.textContent = d.personality_style ?? '—';
        const voiceTag = document.getElementById('voiceTag');
        if (voiceTag) {
            voiceTag.textContent = d.voice_mode ? 'ON — Voice Mode Active' : 'OFF — Markdown Mode';
            voiceTag.className = 'badge fs-6 ' + (d.voice_mode ? 'bg-success' : 'bg-secondary');
        }
        const voiceStatus = document.getElementById('voiceModeStatus');
        if (voiceStatus) {
            voiceStatus.textContent = d.voice_mode ? '🔊 Voice Mode ON' : '📝 Markdown Mode';
            voiceStatus.style.color = d.voice_mode ? '#34D399' : '#9CA3AF';
        }
    }).catch(() => {});
}

function loadBrainContext() {
    apiFetch('/brain/context').then(res => {
        if (!res.success) return;
        const ctx = res.data.context_summary ?? {};
        setText('ctxTopic',    ctx.active_topic    || 'None');
        setText('ctxGoal',     ctx.inferred_goal   || 'None');
        setText('ctxTurns',    ctx.turn_count ?? 0);
        const entities = (ctx.referenced_entities || []);
        setText('ctxEntities', entities.length ? entities.slice(-5).join(', ') : 'None');
    }).catch(() => {});
}

function resetContext() {
    if (!confirm('Reset the active Brain context thread? This will clear topic and entity tracking.')) return;
    apiFetch('/brain/reset-context', { method: 'POST' }).then(res => {
        if (res.success) {
            alert('Context reset acknowledged. Run /brain:reset in the CLI to reset the running process.');
            loadBrainContext();
        }
    }).catch(() => alert('Reset request failed.'));
}

function classifyIntent() {
    const q = document.getElementById('intentInputText').value.trim();
    if (!q) { alert('Enter some text first.'); return; }
    apiFetch('/brain/intent?q=' + encodeURIComponent(q)).then(res => {
        const r = document.getElementById('intentResult');
        r.style.display = 'block';
        if (!res.success) { setText('irIntent', 'Error'); return; }
        const ir = res.data.intent_result ?? {};
        setText('irIntent',     ir.intent       ?? '—');
        setText('irConfidence', (ir.confidence ?? 0) + '%');
        setText('irRouting',    ir.routing_hint ?? '—');
        const entities = ir.entities ?? {};
        setText('irEntities', Object.keys(entities).length ? JSON.stringify(entities) : 'None');
    }).catch(() => setText('irIntent', 'Error'));
}

// Load on page ready
document.addEventListener('DOMContentLoaded', function () {
    loadBrainStatus();
    loadBrainContext();
    setInterval(loadBrainStatus, 15000);
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
