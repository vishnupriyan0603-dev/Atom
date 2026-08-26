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
                    Full TTS synthesis (Google TTS / Web Speech API) is active in Atom Chat.
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
                    Voice duplex audio synthesis is integrated in ATOM Chat with Ben 10 heroic timbre.
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ATOM Brain Knowledge & Level Learning Graph (Phase 1 AI Assistant) -->
<div class="row g-4 mt-1 mb-4">
    <!-- Learning & Concept Synapse Graph -->
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-purple-400"><i class="bi bi-diagram-2-fill me-2"></i>Atom Brain Knowledge &amp; Synapse Graph</span>
                <span class="badge bg-purple-900 text-purple-200 border border-purple-500/40" id="graphTopicCountBadge">6 TOPIC CLUSTERS</span>
            </div>
            <div class="card-body p-3">
                <canvas id="synapseCanvas" class="w-100 bg-black rounded border border-secondary" style="height: 280px; display: block;"></canvas>
                <div class="d-flex justify-content-between align-items-center mt-2 text-xs text-muted">
                    <span><i class="bi bi-circle-fill text-purple-400 me-1"></i> Core Knowledge</span>
                    <span><i class="bi bi-circle-fill text-emerald-400 me-1"></i> Learned Corrections</span>
                    <span><i class="bi bi-circle-fill text-cyan-400 me-1"></i> Real-World Concepts</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Teach Atom Direct Sandbox -->
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary">
                <span class="fw-bold text-emerald-400"><i class="bi bi-mortarboard-fill me-2"></i>Teach Atom Concept / Correction</span>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label text-muted small fw-bold">TOPIC CATEGORY</label>
                    <select id="teachTopicSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="PHP & CodeIgniter">PHP &amp; CodeIgniter</option>
                        <option value="MySQL & Sharded DB">MySQL &amp; Sharded DB</option>
                        <option value="Audio DSP & Binaural 3D">Audio DSP &amp; Binaural 3D</option>
                        <option value="Natural English Conversation">Natural English Conversation</option>
                        <option value="Real-World Pricing & EMI">Real-World Pricing &amp; EMI</option>
                        <option value="Post-Quantum & ZKP Security">Post-Quantum &amp; ZKP Security</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CONCEPT / RULE / CORRECTION</label>
                    <textarea id="teachConceptInput" class="form-control bg-black text-white border-secondary small" rows="3" placeholder="e.g. Always use Level 1 quick answers for basic bike prices, offer Level 3 breakdown only when requested."></textarea>
                </div>
                <button class="btn btn-sm btn-success fw-bold w-100 mb-2" onclick="teachAtomConcept()">
                    <i class="bi bi-send-check me-1"></i> Teach Atom &amp; Update Knowledge Score
                </button>
                <div id="teachFeedback" class="text-xs text-muted p-2 rounded bg-black border border-secondary" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Topic Knowledge Levels Progress Matrix -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-amber-400"><i class="bi bi-bar-chart-line-fill me-2"></i>Topic Knowledge Levels (Level 0 Empty &rarr; Level 6 Expert)</span>
        <span class="badge bg-warning text-dark fw-bold">LEVEL PROGRESSION</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle small">
                <thead class="table-secondary text-uppercase text-muted">
                    <tr>
                        <th>Topic</th>
                        <th>Knowledge Level</th>
                        <th style="width: 35%;">Score / Mastery Meter</th>
                        <th>Confidence</th>
                        <th>Usage Count</th>
                    </tr>
                </thead>
                <tbody id="learningTopicsTableBody">
                    <tr><td colspan="5" class="text-center p-3 text-muted">Loading learning topics...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ATOM Brain Phase 2: Working & Episodic Memory, Sentiment Velocity & Tone Adaptor -->
<div class="row g-4 mb-4">
    <!-- Working & Episodic Memory Inspector -->
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-cyan-400"><i class="bi bi-memory me-2"></i>Episodic &amp; User Facts Memory</span>
                <div>
                    <span class="badge bg-cyan-950 text-cyan-300 border border-cyan-500/40 me-2" id="memoryCountBadge">0 FACTS</span>
                    <button class="btn btn-outline-danger btn-sm py-0 px-2" onclick="clearAllMemory()" title="Clear Working Memory">Clear</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Category</th>
                                <th>Fact / Rule / Preference</th>
                                <th>Confidence</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="memoryFactsTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">No episodic facts stored yet.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer border-secondary bg-black p-2 d-flex justify-content-between text-xs text-muted">
                <span>Working turns in active buffer: <strong id="workingTurnsCount" class="text-white font-monospace">0</strong></span>
                <span>Auto-extracted preferences &amp; explicit <code>/remember</code> facts</span>
            </div>
        </div>
    </div>

    <!-- Sentiment Velocity & Tone Matrix + Remember Sandbox -->
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-activity me-2"></i>Sentiment Velocity &amp; Tone</span>
                <span class="badge bg-info text-dark fw-bold" id="toneBadge">NATURAL</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center p-2 rounded bg-black border border-secondary mb-3">
                    <div>
                        <div class="text-muted text-xs">SENTIMENT VELOCITY</div>
                        <div class="fs-6 fw-bold font-monospace text-emerald-400" id="sentimentVelocityValue">0.0 (Stable)</div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted text-xs">CURRENT STATE</div>
                        <span class="badge bg-secondary text-uppercase" id="currentSentimentState">neutral</span>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label text-muted small fw-bold">REMEMBER NEW USER FACT / PREFERENCE</label>
                    <div class="input-group input-group-sm mb-2">
                        <select id="newFactCategory" class="form-select bg-black text-white border-secondary" style="max-width: 120px;">
                            <option value="preference">preference</option>
                            <option value="rule">rule</option>
                            <option value="tech">tech</option>
                            <option value="general">general</option>
                        </select>
                        <input type="text" id="newFactInput" class="form-control bg-black text-white border-secondary" placeholder="e.g. Always format responses cleanly with bullet points">
                    </div>
                    <button class="btn btn-sm btn-info text-dark fw-bold w-100" onclick="storeNewFact()">
                        <i class="bi bi-save me-1"></i> Store Fact in Episodic Memory
                    </button>
                    <div id="memoryFeedback" class="text-xs mt-2 p-1.5 rounded bg-black border border-secondary text-muted" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ATOM Brain Phase 3: Proactive Situation Reasoner & Minimalist Tool Sandbox -->
<div class="row g-4 mb-4">
    <!-- Real-World Calculation & EMI Simulator -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-emerald-400"><i class="bi bi-calculator me-2"></i>Real-World Financial &amp; EMI Reasoner</span>
                <span class="badge bg-emerald-950 text-emerald-300 border border-emerald-500/40">CALCULATION ENGINE</span>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted text-xs fw-bold">PRINCIPAL (₹)</label>
                        <input type="number" id="emiPrincipal" class="form-control form-control-sm bg-black text-white border-secondary" value="150000" step="1000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted text-xs fw-bold">INTEREST RATE (%)</label>
                        <input type="number" id="emiRate" class="form-control form-control-sm bg-black text-white border-secondary" value="9.5" step="0.1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted text-xs fw-bold">TENURE (MONTHS)</label>
                        <input type="number" id="emiTenure" class="form-control form-control-sm bg-black text-white border-secondary" value="36" step="1">
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-success fw-bold w-100 mb-3" onclick="simulateEmiCalculation()">
                    <i class="bi bi-play-circle me-1"></i> Calculate EMI with Assumptions &amp; Breakdown
                </button>
                <div id="emiResultsBox" class="p-2 rounded bg-black border border-secondary text-xs" style="display:none;">
                    <div class="row text-center mb-2">
                        <div class="col-4">
                            <div class="text-muted">Monthly EMI</div>
                            <div class="fs-6 fw-bold text-success font-monospace" id="emiMonthlyVal">₹0</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Total Interest</div>
                            <div class="fs-6 fw-bold text-warning font-monospace" id="emiInterestVal">₹0</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Total Payable</div>
                            <div class="fs-6 fw-bold text-info font-monospace" id="emiPayableVal">₹0</div>
                        </div>
                    </div>
                    <div class="text-muted text-[11px] border-t border-secondary pt-1" id="emiAssumptionsList"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimalist Tool Sandbox Console -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-purple-400"><i class="bi bi-tools me-2"></i>Minimalist Tool Sandbox</span>
                <span class="badge bg-purple-950 text-purple-300 border border-purple-500/40">SAFE SANDBOX</span>
            </div>
            <div class="card-body">
                <div class="input-group input-group-sm mb-2">
                    <select id="sandboxToolSelect" class="form-select bg-black text-white border-secondary" style="max-width: 150px;">
                        <option value="system_inspect">system_inspect</option>
                        <option value="calc">calc</option>
                        <option value="code_diagnostics">code_diagnostics</option>
                    </select>
                    <input type="text" id="sandboxToolParams" class="form-control bg-black text-white border-secondary" placeholder="Optional params (e.g. 24000 * 1.18 or target=backend)">
                    <button class="btn btn-purple btn-sm text-white fw-bold" style="background:#7C3AED;" onclick="executeSandboxTool()">
                        <i class="bi bi-cpu me-1"></i> Execute
                    </button>
                </div>
                <div class="text-xs text-muted mb-2">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Rule 14 Enforcement: Invoked strictly for verifiable data, not conversational queries.
                </div>
                <pre id="toolOutputPre" class="p-2 bg-black border border-secondary rounded text-xs text-info custom-scroll mb-0" style="max-height: 110px; overflow-y:auto;">{"status": "Sandbox idle. Select a tool to evaluate."}</pre>
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

function loadLearningGraph() {
    apiFetch('/brain/graph').then(res => {
        if (!res.success) return;
        const d = res.data;
        const topics = d.topics || [];

        document.getElementById('graphTopicCountBadge').innerText = `${topics.length} TOPIC CLUSTERS`;

        const tbody = document.getElementById('learningTopicsTableBody');
        tbody.innerHTML = topics.map(t => {
            const score = parseInt(t.score) || 50;
            const progressColor = score >= 85 ? 'bg-success' : (score >= 60 ? 'bg-info' : 'bg-warning');
            const levelBadgeColor = score >= 85 ? 'bg-purple-900 text-purple-200 border border-purple-500' : 'bg-secondary';

            return `
                <tr>
                    <td class="fw-bold text-white"><i class="bi bi-journal-code text-purple-400 me-2"></i>${escapeHtml(t.topic)}</td>
                    <td><span class="badge ${levelBadgeColor}">${escapeHtml(t.level || 'LEARNING')}</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1 bg-black border border-secondary" style="height: 10px;">
                                <div class="progress-bar ${progressColor}" style="width: ${score}%;"></div>
                            </div>
                            <span class="text-xs fw-bold font-monospace">${score}%</span>
                        </div>
                    </td>
                    <td><span class="badge bg-dark border border-secondary text-muted">${escapeHtml(t.confidence || 'MODERATE')}</span></td>
                    <td class="font-monospace text-emerald-400">${t.successful_uses || 0} uses</td>
                </tr>
            `;
        }).join('');

        drawSynapseGraph(topics);
    }).catch(() => {});
}

function drawSynapseGraph(topics) {
    const canvas = document.getElementById('synapseCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = 280;

    const w = canvas.width;
    const h = canvas.height;
    const cx = w / 2;
    const cy = h / 2;

    ctx.clearRect(0, 0, w, h);

    // Center Node (Atom Brain Core)
    ctx.beginPath();
    ctx.arc(cx, cy, 28, 0, Math.PI * 2);
    ctx.fillStyle = '#7C3AED';
    ctx.fill();
    ctx.strokeStyle = '#A78BFA';
    ctx.lineWidth = 3;
    ctx.stroke();

    ctx.fillStyle = '#FFFFFF';
    ctx.font = 'bold 11px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('ATOM', cx, cy - 4);
    ctx.font = '9px sans-serif';
    ctx.fillText('BRAIN', cx, cy + 8);

    // Satellites
    const count = topics.length || 6;
    const radius = Math.min(cx, cy) - 45;

    topics.forEach((t, i) => {
        const angle = (i / count) * Math.PI * 2 - (Math.PI / 2);
        const sx = cx + Math.cos(angle) * radius;
        const sy = cy + Math.sin(angle) * radius;

        // Line to center
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.lineTo(sx, sy);
        ctx.strokeStyle = 'rgba(167, 139, 250, 0.35)';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        // Node
        ctx.beginPath();
        ctx.arc(sx, sy, 18, 0, Math.PI * 2);
        ctx.fillStyle = '#111827';
        ctx.fill();
        ctx.strokeStyle = (parseInt(t.score) >= 85) ? '#10B981' : '#F59E0B';
        ctx.lineWidth = 2;
        ctx.stroke();

        // Topic short name
        ctx.fillStyle = '#E5E7EB';
        ctx.font = '10px sans-serif';
        const label = t.topic.split(' ')[0];
        ctx.fillText(label, sx, sy + 28);
    });
}

function teachAtomConcept() {
    const topic = document.getElementById('teachTopicSelect').value;
    const concept = document.getElementById('teachConceptInput').value.trim();

    if (!concept) {
        alert('Please enter a concept or correction.');
        return;
    }

    apiFetch('/brain/teach', {
        method: 'POST',
        body: JSON.stringify({ topic: topic, concept: concept })
    }).then(res => {
        const fb = document.getElementById('teachFeedback');
        fb.style.display = 'block';
        if (res.success) {
            fb.className = 'text-xs text-emerald-400 p-2 rounded bg-black border border-emerald-500/40';
            fb.innerHTML = `<strong>Learned:</strong> ${escapeHtml(res.data.message || 'Concept recorded!')}`;
            document.getElementById('teachConceptInput').value = '';
            loadLearningGraph();
        } else {
            fb.className = 'text-xs text-danger p-2 rounded bg-black border border-danger';
            fb.innerText = res.message || 'Failed to teach concept.';
        }
    }).catch(e => alert('Teaching error: ' + e.message));
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

function loadBrainMemory() {
    apiFetch('/brain/memory').then(res => {
        if (!res.success) return;
        const d = res.data;
        const facts = d.facts || [];
        const workingCount = d.working_memory_count || 0;
        const velocity = d.sentiment_velocity || {};

        document.getElementById('memoryCountBadge').innerText = `${facts.length} FACTS`;
        document.getElementById('workingTurnsCount').innerText = workingCount;
        
        // Velocity & Tone
        const velVal = velocity.velocity !== undefined ? (velocity.velocity > 0 ? `+${velocity.velocity}` : `${velocity.velocity}`) : '0.0';
        document.getElementById('sentimentVelocityValue').innerText = `${velVal} (${velocity.trend || 'Stable'})`;
        document.getElementById('currentSentimentState').innerText = velocity.current_sentiment || 'neutral';
        document.getElementById('toneBadge').innerText = (velocity.recommended_tone || 'NATURAL').toUpperCase().replace('_', ' ');

        const tbody = document.getElementById('memoryFactsTableBody');
        if (!facts.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center p-3 text-muted">No episodic facts stored yet. Use /remember in chat or the form to add one.</td></tr>';
            return;
        }

        tbody.innerHTML = facts.map(f => `
            <tr>
                <td><span class="badge bg-dark border border-secondary text-info">${escapeHtml(f.category || 'general')}</span></td>
                <td class="text-white">${escapeHtml(f.fact)}</td>
                <td><span class="badge bg-success text-dark font-monospace">${Math.round((f.confidence || 1.0) * 100)}%</span></td>
                <td class="text-end">
                    <button class="btn btn-outline-danger btn-sm py-0 px-2 text-xs" onclick="forgetMemoryFact('${escapeHtml(f.id)}')">
                        <i class="bi bi-x"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }).catch(() => {});
}

function storeNewFact() {
    const category = document.getElementById('newFactCategory').value;
    const fact = document.getElementById('newFactInput').value.trim();

    if (!fact) {
        alert('Please enter a fact or preference.');
        return;
    }

    apiFetch('/brain/memory/remember', {
        method: 'POST',
        body: JSON.stringify({ category: category, fact: fact, confidence: 1.0 })
    }).then(res => {
        const fb = document.getElementById('memoryFeedback');
        fb.style.display = 'block';
        if (res.success) {
            fb.className = 'text-xs mt-2 p-1.5 rounded bg-black border border-info text-info';
            fb.innerHTML = '<strong>Stored:</strong> Fact memorized successfully!';
            document.getElementById('newFactInput').value = '';
            loadBrainMemory();
        } else {
            fb.className = 'text-xs mt-2 p-1.5 rounded bg-black border border-danger text-danger';
            fb.innerText = res.message || 'Failed to remember fact.';
        }
    }).catch(e => alert('Memory error: ' + e.message));
}

function forgetMemoryFact(id) {
    if (!confirm('Forget this stored fact from memory?')) return;
    apiFetch('/brain/memory/forget', {
        method: 'POST',
        body: JSON.stringify({ id: id })
    }).then(res => {
        if (res.success) {
            loadBrainMemory();
        } else {
            alert(res.message || 'Failed to forget fact.');
        }
    }).catch(e => alert('Error forgetting fact: ' + e.message));
}

function clearAllMemory() {
    if (!confirm('Clear working turn history from memory?')) return;
    apiFetch('/brain/memory/forget', {
        method: 'POST',
        body: JSON.stringify({ clear_all: true, working_only: true })
    }).then(res => {
        if (res.success) {
            loadBrainMemory();
        }
    }).catch(() => {});
}

function simulateEmiCalculation() {
    const p = parseFloat(document.getElementById('emiPrincipal').value) || 0;
    const r = parseFloat(document.getElementById('emiRate').value) || 0;
    const t = parseInt(document.getElementById('emiTenure').value) || 0;

    if (p <= 0 || t <= 0) {
        alert('Please enter valid principal and tenure.');
        return;
    }

    const query = `Calculate EMI for ${p} at ${r}% for ${t} months`;
    apiFetch('/brain/reason', {
        method: 'POST',
        body: JSON.stringify({ query: query })
    }).then(res => {
        if (res.success && res.data && res.data.result) {
            const r = res.data.result;
            document.getElementById('emiResultsBox').style.display = 'block';
            document.getElementById('emiMonthlyVal').innerText = `₹${(r.monthly_emi || 0).toLocaleString()}`;
            document.getElementById('emiInterestVal').innerText = `₹${(r.total_interest || 0).toLocaleString()}`;
            document.getElementById('emiPayableVal').innerText = `₹${(r.total_payable || 0).toLocaleString()}`;
            
            const assumptions = r.assumptions || [];
            document.getElementById('emiAssumptionsList').innerHTML = '<strong>Assumptions:</strong> ' + assumptions.join(' • ');
        } else {
            alert('Failed to simulate EMI calculation.');
        }
    }).catch(e => alert('Calculation error: ' + e.message));
}

function executeSandboxTool() {
    const tool = document.getElementById('sandboxToolSelect').value;
    const paramsRaw = document.getElementById('sandboxToolParams').value.trim();

    let params = {};
    if (tool === 'calc') {
        params = { expression: paramsRaw || '150000 * 0.18' };
    } else if (tool === 'code_diagnostics') {
        params = { target: paramsRaw || 'backend' };
    }

    const pre = document.getElementById('toolOutputPre');
    pre.innerText = 'Evaluating tool in sandbox...';

    apiFetch('/brain/tool/execute', {
        method: 'POST',
        body: JSON.stringify({ tool: tool, parameters: params })
    }).then(res => {
        pre.innerText = JSON.stringify(res, null, 2);
    }).catch(e => {
        pre.innerText = 'Error: ' + e.message;
    });
}

// Load on page ready
document.addEventListener('DOMContentLoaded', function () {
    loadBrainStatus();
    loadBrainContext();
    loadLearningGraph();
    loadBrainMemory();
    setInterval(loadBrainStatus, 15000);
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>

