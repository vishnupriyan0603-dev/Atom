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

<!-- ATOM Brain Phase 3: Proactive Situation Reasoner, Multi-Calculator & Tool Sandbox Studio -->
<div class="row g-4 mb-4">
    <!-- Multi-Calculator Suite -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-emerald-400"><i class="bi bi-calculator-fill me-2"></i>Real-World Situation &amp; Cost Calculators</span>
                <ul class="nav nav-pills card-header-pills text-xs" id="calcTabs">
                    <li class="nav-item">
                        <button class="nav-link active py-1 px-2.5 btn-sm text-xs" onclick="switchCalcTab('emi')">Loan EMI</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 px-2.5 btn-sm text-xs text-muted" onclick="switchCalcTab('ev')">Fuel vs EV</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-1 px-2.5 btn-sm text-xs text-muted" onclick="switchCalcTab('server')">Server Sizing</button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <!-- Tab 1: EMI -->
                <div id="tabEmi">
                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <label class="form-label text-muted text-[11px] fw-bold">PRINCIPAL (₹)</label>
                            <input type="number" id="emiPrincipal" class="form-control form-control-sm bg-black text-white border-secondary" value="150000" step="1000">
                        </div>
                        <div class="col-4">
                            <label class="form-label text-muted text-[11px] fw-bold">INTEREST (%)</label>
                            <input type="number" id="emiRate" class="form-control form-control-sm bg-black text-white border-secondary" value="9.5" step="0.1">
                        </div>
                        <div class="col-4">
                            <label class="form-label text-muted text-[11px] fw-bold">MONTHS</label>
                            <input type="number" id="emiTenure" class="form-control form-control-sm bg-black text-white border-secondary" value="36" step="1">
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-success fw-bold w-100 mb-2" onclick="simulateEmiCalculation()">
                        <i class="bi bi-play-circle me-1"></i> Calculate EMI Breakdown
                    </button>
                    <div id="emiResultsBox" class="p-2 rounded bg-black border border-secondary text-xs" style="display:none;">
                        <div class="row text-center mb-1">
                            <div class="col-4">
                                <div class="text-muted text-[10px]">Monthly EMI</div>
                                <div class="fw-bold text-success font-monospace" id="emiMonthlyVal">₹0</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted text-[10px]">Total Interest</div>
                                <div class="fw-bold text-warning font-monospace" id="emiInterestVal">₹0</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted text-[10px]">Total Payable</div>
                                <div class="fw-bold text-info font-monospace" id="emiPayableVal">₹0</div>
                            </div>
                        </div>
                        <div class="text-muted text-[10.5px] border-t border-secondary pt-1" id="emiAssumptionsList"></div>
                    </div>
                </div>

                <!-- Tab 2: Fuel vs EV -->
                <div id="tabEv" style="display:none;">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted text-[11px] fw-bold">DAILY DISTANCE (KM)</label>
                            <input type="number" id="evDailyKm" class="form-control form-control-sm bg-black text-white border-secondary" value="35">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted text-[11px] fw-bold">PETROL PRICE (₹/L)</label>
                            <input type="number" id="evPetrolPrice" class="form-control form-control-sm bg-black text-white border-secondary" value="103">
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-info fw-bold w-100 mb-2" onclick="simulateEvCalculation()">
                        <i class="bi bi-lightning-charge me-1"></i> Compare EV vs Petrol Running Costs
                    </button>
                    <div id="evResultsBox" class="p-2 rounded bg-black border border-secondary text-xs" style="display:none;">
                        <div class="row text-center mb-1">
                            <div class="col-4">
                                <div class="text-muted text-[10px]">EV Running Cost</div>
                                <div class="fw-bold text-success font-monospace" id="evPerKm">₹0.21/km</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted text-[10px]">Petrol Running Cost</div>
                                <div class="fw-bold text-warning font-monospace" id="petrolPerKm">₹2.28/km</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted text-[10px]">Annual Savings</div>
                                <div class="fw-bold text-emerald-400 font-monospace" id="evAnnualSavings">₹0</div>
                            </div>
                        </div>
                        <div class="text-muted text-[10.5px] border-t border-secondary pt-1" id="evSummaryText"></div>
                    </div>
                </div>

                <!-- Tab 3: Server Sizing -->
                <div id="tabServer" style="display:none;">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label text-muted text-[11px] fw-bold">CONCURRENT USERS</label>
                            <input type="number" id="serverUsers" class="form-control form-control-sm bg-black text-white border-secondary" value="1000">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted text-[11px] fw-bold">REQ / USER / SEC</label>
                            <input type="number" id="serverRps" class="form-control form-control-sm bg-black text-white border-secondary" value="2.5" step="0.5">
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-warning fw-bold w-100 mb-2" onclick="simulateServerSizing()">
                        <i class="bi bi-hdd-network me-1"></i> Calculate Cloud Architecture Sizing
                    </button>
                    <div id="serverResultsBox" class="p-2 rounded bg-black border border-secondary text-xs" style="display:none;">
                        <div class="row text-center mb-1">
                            <div class="col-4">
                                <div class="text-muted text-[10px]">CPU Cores</div>
                                <div class="fw-bold text-warning font-monospace" id="serverCores">4 vCPU</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted text-[10px]">RAM Capacity</div>
                                <div class="fw-bold text-info font-monospace" id="serverRam">8 GB</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted text-[10px]">Est. Bandwidth</div>
                                <div class="fw-bold text-success font-monospace" id="serverBandwidth">0 Mbps</div>
                            </div>
                        </div>
                        <div class="text-muted text-[10.5px] border-t border-secondary pt-1" id="serverSummaryText"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimalist Tool Sandbox & Trade-Off Studio -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100 shadow">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-purple-400"><i class="bi bi-tools me-2"></i>Minimalist Tool Sandbox Console</span>
                <span class="badge bg-purple-950 text-purple-300 border border-purple-500/40">5 WHITELISTED TOOLS</span>
            </div>
            <div class="card-body">
                <div class="input-group input-group-sm mb-2">
                    <select id="sandboxToolSelect" class="form-select bg-black text-white border-secondary" style="max-width: 140px;" onchange="onToolSelectChange()">
                        <option value="calc">calc (Math/EMI)</option>
                        <option value="system_inspect">system_inspect</option>
                        <option value="code_diagnostics">code_diagnostics</option>
                        <option value="regex_test">regex_test</option>
                        <option value="json_validate">json_validate</option>
                    </select>
                    <input type="text" id="sandboxToolParams" class="form-control bg-black text-white border-secondary" placeholder="e.g. 150000 * 0.18">
                    <button class="btn btn-purple btn-sm text-white fw-bold" style="background:#7C3AED;" onclick="executeSandboxTool()">
                        <i class="bi bi-cpu me-1"></i> Execute
                    </button>
                </div>
                <div class="text-xs text-muted mb-2">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Rule 14 &amp; 15: Sandboxed whitelisted tools strictly for verifiable system facts and calculations.
                </div>
                <pre id="toolOutputPre" class="p-2 bg-black border border-secondary rounded text-xs text-info custom-scroll mb-0" style="max-height: 140px; overflow-y:auto;">{"status": "Sandbox idle. Ready to evaluate tools."}</pre>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Architectural Decision & Trade-Off Studio -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-info"><i class="bi bi-diagram-3-fill me-2"></i>Architectural &amp; Technology Trade-Off Studio</span>
        <button class="btn btn-outline-info btn-sm py-0 px-2 text-xs" onclick="evaluateTradeOffPreset()">
            <i class="bi bi-sliders me-1"></i> Compare Sample Architecture
        </button>
    </div>
    <div class="card-body p-3">
        <div class="row g-3" id="tradeOffMatrixCards">
            <div class="col-md-6">
                <div class="p-3 rounded bg-black border border-secondary h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-white"><i class="bi bi-database text-info me-2"></i>Option A: MySQL 8.0 (Active Record)</span>
                        <span class="badge bg-success text-dark fw-bold">RECOMMENDED</span>
                    </div>
                    <ul class="text-xs text-muted mb-2 space-y-1">
                        <li><strong class="text-emerald-400">Pros:</strong> Native CodeIgniter integration, battle-tested read throughput, zero extra infrastructure overhead.</li>
                        <li><strong class="text-amber-400">Cons:</strong> Horizontal scaling requires explicit sharding logic.</li>
                    </ul>
                    <div class="text-xs text-info border-t border-secondary/50 pt-1">
                        <strong>Fit:</strong> Perfect for high-frequency transactional data in Atom core.
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 rounded bg-black border border-secondary h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-white"><i class="bi bi-hdd-stack text-purple-400 me-2"></i>Option B: PostgreSQL 16</span>
                        <span class="badge bg-secondary">ALTERNATIVE</span>
                    </div>
                    <ul class="text-xs text-muted mb-2 space-y-1">
                        <li><strong class="text-emerald-400">Pros:</strong> Native JSONB operators, advanced indexing, robust concurrency isolation.</li>
                        <li><strong class="text-amber-400">Cons:</strong> Higher memory footprint per connection, migration complexity.</li>
                    </ul>
                    <div class="text-xs text-purple-300 border-t border-secondary/50 pt-1">
                        <strong>Fit:</strong> Suited for specialized analytics and heavy JSON querying.
                    </div>
                </div>
            </div>
        </div>
<!-- ATOM Brain Phase 4: Voice Duplex & Expressive Speech Prosody Studio -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-cyan-400"><i class="bi bi-soundwave me-2"></i>Voice Duplex &amp; Expressive Speech Prosody Studio</span>
        <span class="badge bg-cyan-950 text-cyan-300 border border-cyan-500/40">SSML &amp; DUPLEX AUDIO</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="mb-2">
                    <label class="form-label text-muted text-xs fw-bold">VOICE PERSONA PROFILE</label>
                    <select id="adminVoiceProfile" class="form-select bg-black text-white border-secondary small" onchange="updateVoiceSliders()">
                        <option value="heroic_ben10">⚡ Heroic Ben 10 (Tamil/EN) - Pitch 1.18x, Rate 1.18x</option>
                        <option value="calm_mentor">🏛️ Calm Engineering Mentor - Pitch 0.95x, Rate 1.05x</option>
                        <option value="empathic_companion">🌱 Empathic Companion - Pitch 1.02x, Rate 0.95x</option>
                        <option value="fast_briefing">⚡ Ultra-Fast Briefing - Pitch 1.05x, Rate 1.35x</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label text-muted text-xs fw-bold">EMOTION MODULATION</label>
                    <select id="adminVoiceEmotion" class="form-select bg-black text-white border-secondary small" onchange="generateVoiceProsody()">
                        <option value="neutral">Neutral (Standard)</option>
                        <option value="excited">Excited / Heroic (+8% Pitch, +6% Velocity)</option>
                        <option value="frustrated">Frustrated Empathy (-6% Pitch, -8% Velocity)</option>
                        <option value="playful">Playful (+5% Pitch, +2% Velocity)</option>
                        <option value="worried">Worried / Reassuring (-4% Pitch, -10% Velocity)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted text-xs fw-bold">SAMPLE TEXT / TAMIL PHONETICS</label>
                    <textarea id="adminVoiceText" class="form-control bg-black text-white border-secondary small" rows="2">வணக்கம்! நான் ATOM. உங்களின் Personal AI Assistant. How can I help your project today?</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-cyan fw-bold flex-grow-1 text-dark" style="background:#22D3EE;" onclick="testVoiceSynthesize()">
                        <i class="bi bi-play-fill me-1"></i> Synthesize &amp; Play Speech
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="stopSpeech()">
                        <i class="bi bi-stop-fill"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-7">
                <div class="p-2 rounded bg-black border border-secondary h-100 flex flex-col justify-between">
                    <div class="d-flex justify-content-between align-items-center mb-1 text-xs">
                        <span class="text-muted fw-bold">W3C SSML GENERATED PAYLOAD</span>
                        <span class="badge bg-secondary text-[10px]" id="ssmlLangBadge">ta-IN</span>
                    </div>
                    <pre id="adminSsmlPre" class="p-2 bg-[#06080b] border border-secondary rounded text-[11px] text-cyan-300 custom-scroll mb-2" style="max-height: 120px; overflow-y:auto;">&lt;speak version="1.0" xml:lang="ta-IN"&gt;&lt;prosody pitch="+18%" rate="+18%"&gt;Loading voice stream...&lt;/prosody&gt;&lt;/speak&gt;</pre>
                    <div class="d-flex justify-content-between text-xs text-muted pt-1 border-t border-secondary/40">
                        <span>Pitch SSML: <strong id="ssmlPitchVal" class="text-white font-monospace">+18%</strong></span>
                        <span>Rate SSML: <strong id="ssmlRateVal" class="text-white font-monospace">+18%</strong></span>
                        <span>Duplex Latency: <strong class="text-emerald-400 font-monospace">~42ms</strong></span>
                    </div>
                </div>
            </div>
<!-- ATOM Brain Phase 5: Autonomous Multi-Step Goal Planner & Self-Correction Studio -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-amber-400"><i class="bi bi-diagram-2-fill me-2"></i>Autonomous Multi-Step Goal Planner &amp; Self-Correction Studio</span>
        <span class="badge bg-amber-950 text-amber-300 border border-amber-500/40">AUTONOMOUS DAG &amp; ERROR RECOVERY</span>
    </div>
    <div class="card-body p-3">
        <div class="row g-3 mb-3">
            <div class="col-md-7">
                <label class="form-label text-muted text-xs fw-bold">HIGH-LEVEL GOAL DIRECTIVE</label>
                <div class="input-group input-group-sm mb-2">
                    <input type="text" id="plannerGoalInput" class="form-control bg-black text-white border-secondary" placeholder="e.g. Migrate database to MySQL 8.0, generate OpenAPI docs, and deploy health check cron">
                    <button class="btn btn-amber btn-sm text-dark fw-bold" style="background:#F59E0B;" onclick="createGoalPlan()">
                        <i class="bi bi-lightning-charge me-1"></i> Decompose Goal (DAG)
                    </button>
                </div>
                <div class="d-flex gap-1.5 flex-wrap">
                    <span class="text-[11px] text-muted me-1">Preset Templates:</span>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-2 text-[11px]" onclick="loadPlanTemplate('db_migration')">📦 DB Migration</button>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-2 text-[11px]" onclick="loadPlanTemplate('security_audit')">🛡️ Security Audit</button>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-2 text-[11px]" onclick="loadPlanTemplate('test_coverage')">🧪 Test Coverage</button>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-2 text-[11px]" onclick="loadPlanTemplate('cicd_deploy')">🚀 CI/CD Deploy</button>
                </div>
            </div>
            <div class="col-md-5">
                <label class="form-label text-muted text-xs fw-bold">PLAN EXECUTION PROGRESS</label>
                <div class="p-2 rounded bg-black border border-secondary">
                    <div class="d-flex justify-content-between text-xs mb-1">
                        <span id="planStatusBadge" class="badge bg-secondary">NO PLAN INITIALIZED</span>
                        <span id="planProgressPercent" class="font-monospace text-amber-400 font-bold">0%</span>
                    </div>
                    <div class="progress bg-dark" style="height: 6px;">
                        <div id="planProgressBar" class="progress-bar bg-amber-400" role="progressbar" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task DAG Stepper Container -->
        <div id="plannerTasksContainer" class="p-3 rounded bg-black border border-secondary" style="min-height: 120px;">
            <div class="text-center text-muted text-xs p-4">
                <i class="bi bi-diagram-2 fs-3 text-secondary d-block mb-2"></i>
                Submit a goal above or select a preset template to generate a multi-step DAG execution plan.
            </div>
<!-- ATOM Brain Phase 6: Meta-Cognition, Self-Evolution & 6-Phase Master Hub -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-pink-400"><i class="bi bi-cpu-fill me-2"></i>Meta-Cognition, Self-Evolution &amp; 6-Phase Master Hub</span>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-pink-950 text-pink-300 border border-pink-500/40">MASTER SYNAPSE v6.0</span>
            <button class="btn btn-outline-pink btn-sm py-0 px-2 text-[11px] text-pink-300 border-pink-500/40" onclick="triggerSelfEvolution()">
                <i class="bi bi-stars me-1"></i> Evolve Synapse
            </button>
        </div>
    </div>
    <div class="card-body p-3">
        <!-- 6-Phase Readiness Radar Grid -->
        <div class="row g-2 mb-3" id="metaPhasesGrid">
            <div class="col-md-2 col-4">
                <div class="p-2 rounded bg-black border border-secondary text-center">
                    <div class="text-[10px] text-muted fw-bold">PHASE 1</div>
                    <div class="text-xs text-white fw-bold truncate">Persona Graph</div>
                    <span class="badge bg-success/20 text-success border border-success/40 text-[9px]">100% READY</span>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-2 rounded bg-black border border-secondary text-center">
                    <div class="text-[10px] text-muted fw-bold">PHASE 2</div>
                    <div class="text-xs text-white fw-bold truncate">Memory &amp; Tone</div>
                    <span class="badge bg-success/20 text-success border border-success/40 text-[9px]">100% READY</span>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-2 rounded bg-black border border-secondary text-center">
                    <div class="text-[10px] text-muted fw-bold">PHASE 3</div>
                    <div class="text-xs text-white fw-bold truncate">Reasoner &amp; Tools</div>
                    <span class="badge bg-success/20 text-success border border-success/40 text-[9px]">100% READY</span>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-2 rounded bg-black border border-secondary text-center">
                    <div class="text-[10px] text-muted fw-bold">PHASE 4</div>
                    <div class="text-xs text-white fw-bold truncate">Voice &amp; Duplex</div>
                    <span class="badge bg-success/20 text-success border border-success/40 text-[9px]">100% READY</span>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-2 rounded bg-black border border-secondary text-center">
                    <div class="text-[10px] text-muted fw-bold">PHASE 5</div>
                    <div class="text-xs text-white fw-bold truncate">Goal DAG Planner</div>
                    <span class="badge bg-success/20 text-success border border-success/40 text-[9px]">100% READY</span>
                </div>
            </div>
            <div class="col-md-2 col-4">
                <div class="p-2 rounded bg-black border border-secondary text-center">
                    <div class="text-[10px] text-muted fw-bold">PHASE 6</div>
                    <div class="text-xs text-white fw-bold truncate">Meta-Cognition</div>
                    <span class="badge bg-pink-900/40 text-pink-300 border border-pink-500/40 text-[9px]">EVOLVING</span>
                </div>
            </div>
        </div>

        <!-- 5-Dimensional Meta-Cognitive Evaluator & Scorecard -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-2.5 rounded bg-black border border-secondary h-100">
                    <div class="text-xs text-muted fw-bold mb-2">LIVE 5D RESPONSE QUALITY EVALUATOR</div>
                    <div class="mb-2">
                        <input type="text" id="metaTestQuery" class="form-control form-control-sm bg-[#0a0d13] text-white border-secondary text-xs" value="Can we scale MySQL horizontally across 5 nodes?">
                    </div>
                    <div class="mb-2">
                        <textarea id="metaTestResponse" class="form-control form-control-sm bg-[#0a0d13] text-white border-secondary text-xs" rows="3">Yes, you can scale MySQL horizontally using active replication clusters with Vitess or ProxySQL sharding. For read-heavy loads, read-replicas provide immediate linear throughput gains.</textarea>
                    </div>
                    <button class="btn btn-sm btn-outline-pink fw-bold w-100 text-pink-300 border-pink-500/40" onclick="evaluateMetaTurn()">
                        <i class="bi bi-search me-1"></i> Run 5-Dimensional Meta Evaluation
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div id="metaScorecardBox" class="p-2.5 rounded bg-black border border-secondary h-100 flex flex-col justify-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-xs text-muted fw-bold">META-COGNITIVE SCORECARD</span>
                            <span id="metaGradeBadge" class="badge bg-success text-dark fw-bold">A+ (96.5%)</span>
                        </div>
                        <div class="space-y-1.5 text-xs" id="metaDimRows">
                            <div class="d-flex justify-content-between text-[11px]">
                                <span class="text-muted">1. Factuality &amp; Grounding</span>
                                <span class="text-emerald-400 font-monospace font-bold">95%</span>
                            </div>
                            <div class="d-flex justify-content-between text-[11px]">
                                <span class="text-muted">2. Persona Consistency</span>
                                <span class="text-emerald-400 font-monospace font-bold">96%</span>
                            </div>
                            <div class="d-flex justify-content-between text-[11px]">
                                <span class="text-muted">3. Conciseness &amp; Precision</span>
                                <span class="text-emerald-400 font-monospace font-bold">92%</span>
                            </div>
                            <div class="d-flex justify-content-between text-[11px]">
                                <span class="text-muted">4. Tool Appropriateness (Rule 14)</span>
                                <span class="text-emerald-400 font-monospace font-bold">98%</span>
                            </div>
                            <div class="d-flex justify-content-between text-[11px]">
                                <span class="text-muted">5. Safety &amp; Redaction Integrity</span>
                                <span class="text-emerald-400 font-monospace font-bold">100%</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-[11px] text-pink-300 border-t border-secondary/50 pt-1 mt-2" id="metaVerdictText">
                        <i class="bi bi-shield-check text-success me-1"></i><strong>Verdict:</strong> HIGH_CALIBRATION • Optimal response quality with zero hallucination markers.
                    </div>
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

function switchCalcTab(tab) {
    document.getElementById('tabEmi').style.display = (tab === 'emi') ? 'block' : 'none';
    document.getElementById('tabEv').style.display = (tab === 'ev') ? 'block' : 'none';
    document.getElementById('tabServer').style.display = (tab === 'server') ? 'block' : 'none';

    const buttons = document.querySelectorAll('#calcTabs .nav-link');
    buttons.forEach((btn, idx) => {
        const isActive = (tab === 'emi' && idx === 0) || (tab === 'ev' && idx === 1) || (tab === 'server' && idx === 2);
        btn.className = isActive ? 'nav-link active py-1 px-2.5 btn-sm text-xs' : 'nav-link py-1 px-2.5 btn-sm text-xs text-muted';
    });
}

function simulateEvCalculation() {
    const km = parseFloat(document.getElementById('evDailyKm').value) || 35;
    const petrol = parseFloat(document.getElementById('evPetrolPrice').value) || 103;

    apiFetch('/brain/tool/execute', {
        method: 'POST',
        body: JSON.stringify({
            tool: 'calc',
            parameters: { expression: `((${km} / 45) * ${petrol}) - ((${km} / 35) * 7.5)` }
        })
    }).then(res => {
        const petCostPerKm = petrol / 45;
        const evCostPerKm = 7.5 / 35;
        const dailySavings = (km * petCostPerKm) - (km * evCostPerKm);
        const annualSavings = dailySavings * 365;

        document.getElementById('evResultsBox').style.display = 'block';
        document.getElementById('evPerKm').innerText = `₹${evCostPerKm.toFixed(2)}/km`;
        document.getElementById('petrolPerKm').innerText = `₹${petCostPerKm.toFixed(2)}/km`;
        document.getElementById('evAnnualSavings').innerText = `₹${Math.round(annualSavings).toLocaleString()}`;
        document.getElementById('evSummaryText').innerHTML = `<strong>EV Efficiency:</strong> Saves ~₹${Math.round(annualSavings).toLocaleString()}/year over ${km} km daily commute (89% running cost reduction).`;
    }).catch(e => alert('EV Calculation error: ' + e.message));
}

function simulateServerSizing() {
    const users = parseInt(document.getElementById('serverUsers').value) || 1000;
    const rps = parseFloat(document.getElementById('serverRps').value) || 2.5;

    const totalRps = users * rps;
    const cores = Math.max(2, Math.ceil(totalRps / 200));
    const ram = Math.max(4, Math.ceil((users * 0.05 * 50) / 1024) + 2);
    const mbps = (totalRps * 15 * 8) / 1024;

    document.getElementById('serverResultsBox').style.display = 'block';
    document.getElementById('serverCores').innerText = `${cores} vCPU`;
    document.getElementById('serverRam').innerText = `${ram} GB`;
    document.getElementById('serverBandwidth').innerText = `${mbps.toFixed(1)} Mbps`;
    document.getElementById('serverSummaryText').innerHTML = `<strong>Architecture Spec:</strong> Dedicated ${cores} vCPU / ${ram} GB Cloud Droplet / VPS for ${users.toLocaleString()} users at ${totalRps.toLocaleString()} RPS.`;
}

function onToolSelectChange() {
    const tool = document.getElementById('sandboxToolSelect').value;
    const input = document.getElementById('sandboxToolParams');
    if (tool === 'calc') input.placeholder = 'e.g. (150000 * 0.18) + 2500';
    else if (tool === 'regex_test') input.placeholder = 'e.g. pattern=^[a-z0-9_]+$ & subject=user_123';
    else if (tool === 'json_validate') input.placeholder = 'e.g. {"status":"ok","code":200}';
    else if (tool === 'code_diagnostics') input.placeholder = 'target=backend';
    else input.placeholder = 'No parameters required';
}

function executeSandboxTool() {
    const tool = document.getElementById('sandboxToolSelect').value;
    const paramsRaw = document.getElementById('sandboxToolParams').value.trim();

    let params = {};
    if (tool === 'calc') {
        params = { expression: paramsRaw || '150000 * 0.18' };
    } else if (tool === 'code_diagnostics') {
        params = { target: paramsRaw || 'backend' };
    } else if (tool === 'regex_test') {
        params = { pattern: paramsRaw || '^[a-zA-Z0-9_]+$', subject: 'atom_brain_user_42' };
    } else if (tool === 'json_validate') {
        params = { json: paramsRaw || '{"service":"Atom Brain","version":"3.0","status":"active"}' };
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

function evaluateTradeOffPreset() {
    const matrix = document.getElementById('tradeOffMatrixCards');
    matrix.innerHTML = `
        <div class="col-md-6">
            <div class="p-3 rounded bg-black border border-secondary h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-white"><i class="bi bi-cpu text-info me-2"></i>Option A: Monolith (PHP / CI4)</span>
                    <span class="badge bg-success text-dark fw-bold">RECOMMENDED</span>
                </div>
                <ul class="text-xs text-muted mb-2 space-y-1">
                    <li><strong class="text-emerald-400">Pros:</strong> Instant deployment, zero network latency between modules, single unified codebase.</li>
                    <li><strong class="text-amber-400">Cons:</strong> Scaling requires scaling the entire container/server.</li>
                </ul>
                <div class="text-xs text-info border-t border-secondary/50 pt-1">
                    <strong>Verdict:</strong> Maximum velocity and developer ergonomics for ATOM core.
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-3 rounded bg-black border border-secondary h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-white"><i class="bi bi-boxes text-purple-400 me-2"></i>Option B: Microservices (gRPC / K8s)</span>
                    <span class="badge bg-secondary">ALTERNATIVE</span>
                </div>
                <ul class="text-xs text-muted mb-2 space-y-1">
                    <li><strong class="text-emerald-400">Pros:</strong> Independent language runtimes, modular scale boundaries.</li>
                    <li><strong class="text-amber-400">Cons:</strong> High DevOps tax, distributed debugging complexity.</li>
                </ul>
                <div class="text-xs text-purple-300 border-t border-secondary/50 pt-1">
                    <strong>Verdict:</strong> Reserved for specialized heavy workloads (e.g. Wasm Sandbox).
                </div>
            </div>
        </div>
    `;
}

let currentUtterance = null;

function updateVoiceSliders() {
    generateVoiceProsody();
}

function generateVoiceProsody() {
    const text = document.getElementById('adminVoiceText').value.trim();
    const profile = document.getElementById('adminVoiceProfile').value;
    const emotion = document.getElementById('adminVoiceEmotion').value;

    if (!text) return;

    apiFetch('/brain/voice/synthesize', {
        method: 'POST',
        body: JSON.stringify({ text: text, profile: profile, emotion: emotion })
    }).then(res => {
        if (res.success && res.data) {
            const d = res.data;
            document.getElementById('adminSsmlPre').innerText = d.ssml || '';
            document.getElementById('ssmlLangBadge').innerText = d.is_tamil ? 'ta-IN (Tamil)' : (d.profile?.lang || 'en-US');
            document.getElementById('ssmlPitchVal').innerText = d.prosody?.pitch_ssml || '+18%';
            document.getElementById('ssmlRateVal').innerText = d.prosody?.rate_ssml || '+18%';
        }
    }).catch(() => {});
}

function testVoiceSynthesize() {
    const text = document.getElementById('adminVoiceText').value.trim();
    const profile = document.getElementById('adminVoiceProfile').value;
    const emotion = document.getElementById('adminVoiceEmotion').value;

    if (!text) {
        alert('Please enter some text to synthesize.');
        return;
    }

    if (!('speechSynthesis' in window)) {
        alert('Speech Synthesis API not supported in this browser.');
        return;
    }

    window.speechSynthesis.cancel();

    apiFetch('/brain/voice/synthesize', {
        method: 'POST',
        body: JSON.stringify({ text: text, profile: profile, emotion: emotion })
    }).then(res => {
        if (!res.success || !res.data) {
            alert('Voice synthesis failed.');
            return;
        }

        const d = res.data;
        const wp = d.web_speech_params;
        const utterance = new SpeechSynthesisUtterance(wp.text);
        utterance.pitch = wp.pitch;
        utterance.rate = wp.rate;
        utterance.volume = wp.volume;
        utterance.lang = wp.lang;

        const voices = window.speechSynthesis.getVoices();
        if (voices.length > 0) {
            if (d.is_tamil) {
                const tv = voices.find(v => v.lang && (v.lang.startsWith('ta') || v.name.toLowerCase().includes('tamil')));
                if (tv) utterance.voice = tv;
            } else {
                const vMatch = voices.find(v => v.lang && v.lang.startsWith(wp.lang.split('-')[0]));
                if (vMatch) utterance.voice = vMatch;
            }
        }

        currentUtterance = utterance;
        window.speechSynthesis.speak(utterance);
    }).catch(e => alert('Speech error: ' + e.message));
}

function stopSpeech() {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
}

let activeGoalPlan = null;

function loadPlanTemplate(templateKey) {
    apiFetch('/brain/planner/create', {
        method: 'POST',
        body: JSON.stringify({ template: templateKey })
    }).then(res => {
        if (res.success && res.data) {
            activeGoalPlan = res.data;
            document.getElementById('plannerGoalInput').value = res.data.goal || '';
            renderPlanTasks();
        }
    }).catch(e => alert('Template error: ' + e.message));
}

function createGoalPlan() {
    const goal = document.getElementById('plannerGoalInput').value.trim();
    if (!goal) {
        alert('Please enter a goal description.');
        return;
    }

    apiFetch('/brain/planner/create', {
        method: 'POST',
        body: JSON.stringify({ goal: goal })
    }).then(res => {
        if (res.success && res.data) {
            activeGoalPlan = res.data;
            renderPlanTasks();
        }
    }).catch(e => alert('Planner error: ' + e.message));
}

function renderPlanTasks() {
    if (!activeGoalPlan) return;

    const p = activeGoalPlan;
    const badge = document.getElementById('planStatusBadge');
    badge.innerText = p.status.toUpperCase();
    badge.className = p.status === 'completed' ? 'badge bg-success text-dark font-bold' : (p.status === 'in_progress' ? 'badge bg-warning text-dark font-bold' : 'badge bg-secondary');

    const percent = `${p.progress_percent || 0}%`;
    document.getElementById('planProgressPercent').innerText = percent;
    document.getElementById('planProgressBar').style.width = percent;

    const container = document.getElementById('plannerTasksContainer');
    const tasks = p.tasks || [];

    if (!tasks.length) {
        container.innerHTML = '<div class="text-muted text-xs text-center p-3">No tasks in current plan.</div>';
        return;
    }

    container.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-b border-secondary/50">
            <div>
                <span class="text-white fw-bold text-xs"><i class="bi bi-diagram-3 text-amber-400 me-1"></i>${escapeHtml(p.goal)}</span>
                <span class="badge bg-dark border border-secondary text-muted ms-2 text-[10px]">${p.template_used}</span>
            </div>
            <span class="text-xs text-muted font-monospace">${p.completed_tasks} / ${p.total_tasks} Tasks Done</span>
        </div>
        <div class="space-y-2">
            ${tasks.map((t, idx) => {
                const statusColor = t.status === 'completed' ? 'text-success' : (t.status === 'self_correcting' ? 'text-warning' : (t.status === 'failed_unrecoverable' ? 'text-danger' : 'text-muted'));
                const icon = t.status === 'completed' ? 'bi-check-circle-fill text-success' : (t.status === 'self_correcting' ? 'bi-arrow-repeat text-warning' : 'bi-circle');
                const isReady = (t.dependencies || []).every(depId => (tasks.find(x => x.id === depId) || {}).status === 'completed');

                return `
                    <div class="p-2.5 rounded bg-[#0b0e14] border border-secondary/60 text-xs">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi ${icon}"></i>
                                <span class="fw-bold text-white">${idx + 1}. ${escapeHtml(t.title)}</span>
                                <span class="badge bg-black border border-secondary text-[10px] text-muted">${t.action}</span>
                            </div>
                            <div class="d-flex gap-1">
                                ${t.status !== 'completed' ? `
                                    <button class="btn btn-outline-success btn-sm py-0 px-2 text-[11px]" onclick="executePlanStep('${t.id}', true)" ${!isReady ? 'disabled' : ''}>
                                        <i class="bi bi-play"></i> Execute
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm py-0 px-1.5 text-[11px]" onclick="executePlanStep('${t.id}', false)" ${!isReady ? 'disabled' : ''} title="Simulate Failure & Trigger Self-Correction">
                                        <i class="bi bi-bug"></i> Test Fail
                                    </button>
                                ` : '<span class="badge bg-success/20 text-success border border-success/40">COMPLETED</span>'}
                            </div>
                        </div>
                        ${t.error ? `
                            <div class="p-1.5 rounded bg-red-950/40 border border-red-500/40 text-danger text-[11px] mt-1">
                                <strong>Error:</strong> ${escapeHtml(t.error)}
                                ${t.recovery_strategy ? `
                                    <div class="text-warning mt-1">
                                        <i class="bi bi-tools me-1"></i><strong>Self-Correction:</strong> ${escapeHtml(t.recovery_strategy.remediation_plan)}
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                        ${t.output ? `<div class="text-emerald-400 text-[11px] mt-1 font-mono"><i class="bi bi-check me-1"></i>${escapeHtml(t.output)}</div>` : ''}
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

function executePlanStep(taskId, simulateSuccess) {
    if (!activeGoalPlan) return;

    apiFetch('/brain/planner/step', {
        method: 'POST',
        body: JSON.stringify({
            plan: activeGoalPlan,
            task_id: taskId,
            success: simulateSuccess,
            error: simulateSuccess ? null : 'Simulated execution lock / timeout failure'
        })
    }).then(res => {
        if (res.success && res.data && res.data.plan) {
            activeGoalPlan = res.data.plan;
            renderPlanTasks();
        } else {
            alert(res.message || res.error || 'Step execution failed.');
        }
    }).catch(e => alert('Step execution error: ' + e.message));
}

function evaluateMetaTurn() {
    const input = document.getElementById('metaTestQuery').value.trim();
    const response = document.getElementById('metaTestResponse').value.trim();

    if (!input || !response) {
        alert('Please enter both query and response to evaluate.');
        return;
    }

    apiFetch('/brain/meta/evaluate', {
        method: 'POST',
        body: JSON.stringify({ input: input, response: response })
    }).then(res => {
        if (res.success && res.data) {
            const d = res.data;
            const dims = d.dimensions || {};

            document.getElementById('metaGradeBadge').innerText = `${d.grade} (${d.composite_score}%)`;
            document.getElementById('metaGradeBadge').className = d.composite_score >= 90 ? 'badge bg-success text-dark fw-bold' : (d.composite_score >= 80 ? 'badge bg-info text-dark fw-bold' : 'badge bg-warning text-dark fw-bold');

            document.getElementById('metaDimRows').innerHTML = `
                <div class="d-flex justify-content-between text-[11px]">
                    <span class="text-muted">1. Factuality &amp; Grounding</span>
                    <span class="text-emerald-400 font-monospace font-bold">${dims.factuality?.score || 0}%</span>
                </div>
                <div class="d-flex justify-content-between text-[11px]">
                    <span class="text-muted">2. Persona Consistency</span>
                    <span class="text-emerald-400 font-monospace font-bold">${dims.persona_consistency?.score || 0}%</span>
                </div>
                <div class="d-flex justify-content-between text-[11px]">
                    <span class="text-muted">3. Conciseness &amp; Precision</span>
                    <span class="text-emerald-400 font-monospace font-bold">${dims.conciseness?.score || 0}%</span>
                </div>
                <div class="d-flex justify-content-between text-[11px]">
                    <span class="text-muted">4. Tool Appropriateness (Rule 14)</span>
                    <span class="text-emerald-400 font-monospace font-bold">${dims.tool_appropriateness?.score || 0}%</span>
                </div>
                <div class="d-flex justify-content-between text-[11px]">
                    <span class="text-muted">5. Safety &amp; Redaction Integrity</span>
                    <span class="text-emerald-400 font-monospace font-bold">${dims.safety_integrity?.score || 0}%</span>
                </div>
            `;

            document.getElementById('metaVerdictText').innerHTML = `<i class="bi bi-shield-check text-success me-1"></i><strong>Verdict:</strong> ${d.meta_verdict} • Grade ${d.grade}`;
        }
    }).catch(e => alert('Meta evaluation error: ' + e.message));
}

function triggerSelfEvolution() {
    apiFetch('/brain/meta/evolve', {
        method: 'POST',
        body: JSON.stringify({})
    }).then(res => {
        if (res.success) {
            alert(res.message || 'Synapse weights evolved successfully!');
            loadMetaTelemetry();
        }
    }).catch(e => alert('Evolution error: ' + e.message));
}

function loadMetaTelemetry() {
    apiFetch('/brain/meta/telemetry').then(res => {
        if (res.success && res.data) {
            // Master telemetry refreshed
        }
    }).catch(() => {});
}

// Load on page ready
document.addEventListener('DOMContentLoaded', function () {
    loadBrainStatus();
    loadBrainContext();
    loadLearningGraph();
    loadBrainMemory();
    generateVoiceProsody();
    loadPlanTemplate('db_migration');
    loadMetaTelemetry();
    setInterval(loadBrainStatus, 15000);
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>

