<?php
// ATOM Web Admin — Phase 102: Autonomous Event-Driven CQRS State Sourcing & Real-Time Time-Travel Ledger Engine
$pageTitle = "CQRS Event Sourcing & Time-Travel Ledger (Phase 102)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #38BDF8;">CQRS Event Sourcing &amp; Time-Travel Ledger</h2>
        <p class="text-muted small mb-0">Phase 102: Immutable Append-Only Event Streams, SHA-256 Chaining &amp; Deterministic Historical Replay</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info fw-bold text-dark" style="background-color: #38BDF8; border-color: #0284C7;" onclick="verifyLedgerIntegrity()">
            <i class="bi bi-shield-check me-1"></i> Verify Cryptographic Chain
        </button>
    </div>
</div>

<!-- Ledger Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL STREAM EVENTS</div>
            <div class="fs-4 fw-bold text-info" id="metricTotalEvents">3 Events</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">AGGREGATE VERSION</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricAggregateVersion">v3 Active</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CHAIN INTEGRITY</div>
            <div class="fs-4 fw-bold text-success" id="metricChainIntegrity">SHA-256 VERIFIED</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CQRS PROJECTIONS</div>
            <div class="fs-4 fw-bold text-purple-400">1 Live View</div>
        </div>
    </div>
</div>

<!-- Time-Travel Scrubber & Command Dispatcher -->
<div class="row g-4 mb-4">
    <!-- Time-Travel Rewind Simulator -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-clock-history me-2"></i>Deterministic Time-Travel State Reconstructor</span>
                <span id="timeTravelModeBadge" class="badge bg-success/20 text-success border border-success/40">HEAD (LATEST)</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between">
                        <span>SCRUB TIMELINE VERSION</span>
                        <span id="versionSliderLabel" class="text-info font-monospace font-bold">Version: 3 / 3</span>
                    </label>
                    <input type="range" class="form-range" id="versionSlider" min="1" max="3" value="3" oninput="onSliderChange(this.value)">
                </div>

                <div class="p-3 rounded bg-black border border-secondary mb-3">
                    <div class="text-muted small mb-1 fw-bold">RECONSTRUCTED STATE SNAPSHOT</div>
                    <pre id="reconstructedStatePre" class="text-info text-xs custom-scroll mb-0" style="max-height: 160px; overflow-y:auto;">{"status": "Loading snapshot..."}</pre>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-outline-info btn-sm flex-grow-1" onclick="stepBackVersion()">
                        <i class="bi bi-skip-backward-fill me-1"></i> Step Back
                    </button>
                    <button class="btn btn-outline-info btn-sm flex-grow-1" onclick="stepForwardVersion()">
                        <i class="bi bi-skip-forward-fill me-1"></i> Step Forward
                    </button>
                    <button class="btn btn-sm btn-info text-dark fw-bold" onclick="resetToHead()">
                        <i class="bi bi-fast-forward-fill me-1"></i> Head
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Command Dispatcher (CQRS Command Side) -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-emerald-400"><i class="bi bi-send-check me-2"></i>CQRS Command Dispatcher</span>
                <span class="badge bg-emerald-950 text-emerald-300 border border-emerald-500/40">APPEND-ONLY</span>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label text-muted small fw-bold">COMMAND TYPE</label>
                    <select id="commandTypeSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="UpdateAgentPersona">UpdateAgentPersona (Mutate Persona &amp; Tone)</option>
                        <option value="RecordMemoryFact">RecordMemoryFact (Append Fact to Ledger)</option>
                        <option value="DeployPipeline">DeployPipeline (Record Release State)</option>
                        <option value="MigrateDatabase">MigrateDatabase (Execute Migration Checkpoint)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">PAYLOAD (JSON)</label>
                    <textarea id="commandPayloadText" class="form-control bg-black text-white border-secondary small font-monospace" rows="3">{"persona": "Ultra-Fast Briefing", "depth": 2, "status": "active"}</textarea>
                </div>
                <button class="btn btn-sm btn-outline-success fw-bold w-100" onclick="dispatchCustomCommand()">
                    <i class="bi bi-plus-circle me-1"></i> Dispatch Command &amp; Append SHA-256 Chained Event
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Immutable Event Stream Ledger Table -->
<div class="card bg-dark border-secondary text-white mb-4 shadow">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold text-white"><i class="bi bi-journal-code text-info me-2"></i>Immutable Event Stream Ledger (Aggregate: workspace-atom-core)</span>
        <span class="badge bg-dark border border-secondary text-muted">CRYPTOGRAPHIC CHAIN</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover table-striped mb-0 text-xs">
                <thead>
                    <tr class="text-muted border-secondary">
                        <th>VER</th>
                        <th>EVENT ID</th>
                        <th>EVENT TYPE</th>
                        <th>PAYLOAD</th>
                        <th>SHA-256 CHECKSUM</th>
                        <th>TIMESTAMP</th>
                    </tr>
                </thead>
                <tbody id="eventLedgerTableBody">
                    <tr><td colspan="6" class="text-center p-3 text-muted">Loading immutable event stream...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const AGGREGATE_ID = 'workspace-atom-core';
let maxVersion = 3;
let currentViewingVersion = 3;

function loadEventStream() {
    apiFetch(`/infrastructure/events/stream?aggregate_id=${encodeURIComponent(AGGREGATE_ID)}`).then(res => {
        if (!res.success || !res.data) return;
        const d = res.data;
        const events = d.events || [];
        maxVersion = d.current_version || events.length;

        document.getElementById('metricTotalEvents').innerText = `${events.length} Events`;
        document.getElementById('metricAggregateVersion').innerText = `v${maxVersion} Active`;

        const slider = document.getElementById('versionSlider');
        slider.max = maxVersion;
        if (currentViewingVersion > maxVersion) currentViewingVersion = maxVersion;
        slider.value = currentViewingVersion;
        document.getElementById('versionSliderLabel').innerText = `Version: ${currentViewingVersion} / ${maxVersion}`;

        const tbody = document.getElementById('eventLedgerTableBody');
        if (!events.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center p-3 text-muted">No events recorded in ledger.</td></tr>';
            return;
        }

        tbody.innerHTML = events.map(e => `
            <tr>
                <td><span class="badge bg-info text-dark font-monospace font-bold">v${e.version}</span></td>
                <td class="font-monospace text-muted">${escapeHtml(e.event_id)}</td>
                <td class="fw-bold text-white">${escapeHtml(e.event_type)}</td>
                <td class="font-monospace text-cyan-300">${escapeHtml(JSON.stringify(e.payload))}</td>
                <td class="font-monospace text-[10px] text-emerald-400" title="${escapeHtml(e.checksum)}">${escapeHtml(e.checksum.substring(0, 16))}...</td>
                <td class="text-muted">${escapeHtml(e.timestamp.substring(11, 19))}</td>
            </tr>
        `).join('');

        loadTimeTravelState(currentViewingVersion);
    }).catch(e => console.error('Ledger stream error:', e));
}

function loadTimeTravelState(ver) {
    currentViewingVersion = parseInt(ver) || 1;
    document.getElementById('versionSliderLabel').innerText = `Version: ${currentViewingVersion} / ${maxVersion}`;

    const badge = document.getElementById('timeTravelModeBadge');
    if (currentViewingVersion === maxVersion) {
        badge.innerText = 'HEAD (LATEST)';
        badge.className = 'badge bg-success/20 text-success border border-success/40';
    } else {
        badge.innerText = `REWOUND (v${currentViewingVersion})`;
        badge.className = 'badge bg-warning/20 text-warning border border-warning/40';
    }

    apiFetch('/infrastructure/events/timetravel', {
        method: 'POST',
        body: JSON.stringify({ aggregate_id: AGGREGATE_ID, version: currentViewingVersion })
    }).then(res => {
        const pre = document.getElementById('reconstructedStatePre');
        if (res.success && res.data) {
            pre.innerText = JSON.stringify(res.data.reconstructed_state, null, 2);
        } else {
            pre.innerText = 'Failed to reconstruct state.';
        }
    }).catch(e => {
        document.getElementById('reconstructedStatePre').innerText = 'Error: ' + e.message;
    });
}

function onSliderChange(val) {
    loadTimeTravelState(val);
}

function stepBackVersion() {
    if (currentViewingVersion > 1) {
        document.getElementById('versionSlider').value = currentViewingVersion - 1;
        loadTimeTravelState(currentViewingVersion - 1);
    }
}

function stepForwardVersion() {
    if (currentViewingVersion < maxVersion) {
        document.getElementById('versionSlider').value = currentViewingVersion + 1;
        loadTimeTravelState(currentViewingVersion + 1);
    }
}

function resetToHead() {
    document.getElementById('versionSlider').value = maxVersion;
    loadTimeTravelState(maxVersion);
}

function dispatchCustomCommand() {
    const cmd = document.getElementById('commandTypeSelect').value;
    const rawPayload = document.getElementById('commandPayloadText').value.trim();

    let payload = {};
    try {
        payload = JSON.parse(rawPayload);
    } catch (e) {
        alert('Invalid JSON in payload: ' + e.message);
        return;
    }

    apiFetch('/infrastructure/events/dispatch', {
        method: 'POST',
        body: JSON.stringify({
            aggregate_id: AGGREGATE_ID,
            command_type: cmd,
            payload: payload
        })
    }).then(res => {
        if (res.success) {
            currentViewingVersion = maxVersion + 1;
            loadEventStream();
        } else {
            alert('Command dispatch failed: ' + (res.message || res.error));
        }
    }).catch(e => alert('Dispatch error: ' + e.message));
}

function verifyLedgerIntegrity() {
    apiFetch(`/infrastructure/events/verify?aggregate_id=${encodeURIComponent(AGGREGATE_ID)}`).then(res => {
        if (res.success && res.data) {
            alert(`✅ Cryptographic Ledger Verification Passed!\nTotal chained events: ${res.data.total_events}\nStatus: ${res.data.chain_status}`);
        } else {
            alert('❌ Verification failed: ' + (res.message || 'Corrupted ledger'));
        }
    }).catch(e => alert('Verification error: ' + e.message));
}

document.addEventListener('DOMContentLoaded', function () {
    loadEventStream();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
