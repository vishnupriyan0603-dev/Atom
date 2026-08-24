<?php
// ATOM Web Admin — Phase 27: Desktop Automation & Native OS Sidecar Dashboard
$pageTitle = "Desktop Automation & Native OS Sidecar";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F43F5E;">Desktop Automation &amp; OS Sidecar</h2>
        <p class="text-muted small mb-0">Native OS integration — Active window detection, clipboard intelligence, notifications &amp; system automation</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm" style="background: linear-gradient(135deg, #F43F5E 0%, #E11D48 100%); border: none; color: white;" onclick="sendTestNotification()">
            <i class="bi bi-bell me-1"></i> Test Notification
        </button>
    </div>
</div>

<!-- Desktop Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SIDECAR STATUS</div>
            <div class="fs-4 fw-bold text-success" id="metricSidecarStatus">ONLINE</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE APPLICATION</div>
            <div class="fs-4 fw-bold text-info text-truncate" id="metricAppName">VS Code</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">POWER / BATTERY</div>
            <div class="fs-4 fw-bold text-warning" id="metricBattery">92% (AC)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DEV PROCESSES</div>
            <div class="fs-4 fw-bold" style="color:#F43F5E;" id="metricProcesses">7 ACTIVE</div>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- Panel 1: Active Window & Context Inspector -->
    <div class="col-md-5">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#F43F5E;"><i class="bi bi-window me-2"></i>Active Window &amp; Developer Context</span>
                <span class="badge bg-danger">FOREGROUND</span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-dark table-borderless mb-3">
                    <tbody>
                        <tr><td class="text-muted">Window Title</td><td class="text-truncate fw-bold text-info" id="diagTitle" style="max-width: 200px;">ATOM Workspace — VS Code</td></tr>
                        <tr><td class="text-muted">Application</td><td id="diagApp">Visual Studio Code</td></tr>
                        <tr><td class="text-muted">OS Platform</td><td id="diagPlatform">Windows</td></tr>
                        <tr><td class="text-muted">Volume Level</td><td id="diagVolume">75% (Unmuted)</td></tr>
                    </tbody>
                </table>
                <h6 class="fw-bold text-muted small mt-4 mb-2">RUNNING DEVELOPER TOOLS</h6>
                <ul class="list-group list-group-flush bg-transparent" id="devToolsList" style="font-size: 13px;">
                    <li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between">
                        <span><i class="bi bi-code-square text-info me-2"></i>Visual Studio Code</span>
                        <span class="badge bg-success">ACTIVE</span>
                    </li>
                    <li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between">
                        <span><i class="bi bi-terminal text-warning me-2"></i>Windows Terminal</span>
                        <span class="badge bg-secondary">RUNNING</span>
                    </li>
                    <li class="list-group-item bg-transparent text-white border-secondary d-flex justify-content-between">
                        <span><i class="bi bi-browser-chrome text-primary me-2"></i>Google Chrome</span>
                        <span class="badge bg-secondary">RUNNING</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Panel 2: Clipboard Intelligence Inspector -->
    <div class="col-md-7">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#38BDF8;"><i class="bi bi-clipboard-data me-2"></i>Clipboard Intelligence</span>
                <span class="badge bg-info text-dark" id="clipboardTypeBadge">PLAIN_TEXT</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Clipboard Buffer Text</label>
                    <textarea class="form-control bg-dark text-white border-secondary" id="clipboardInputArea" rows="5" placeholder="Paste any code, JSON, SQL query, or error message here to inspect..." oninput="analyzeClipboardLive()"></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted small" id="clipboardSummary">Paste content to trigger automatic intelligence classification.</span>
                    <button class="btn btn-outline-info btn-sm" onclick="analyzeClipboardLive()"><i class="bi bi-cpu me-1"></i> Analyze Buffer</button>
                </div>
                <label class="form-label text-muted small fw-bold">Suggested AI Context Actions</label>
                <div class="d-flex flex-wrap gap-2" id="suggestedActionsArea">
                    <span class="text-muted small">No actions available.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel 3: System Control & Notification Sandbox -->
    <div class="col-12">
        <div class="card bg-dark border-secondary text-white">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#F59E0B;"><i class="bi bi-sliders me-2"></i>System Control &amp; Notification Sandbox</span>
                <span class="badge bg-warning text-dark">GOVERNED</span>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Send Native Toast Notification</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-dark text-white border-secondary" id="notifyMessageInput" placeholder="Enter notification message..." value="Hello Vichu! Atom Desktop Sidecar is active.">
                            <button class="btn btn-warning" onclick="sendCustomNotification()"><i class="bi bi-send-fill me-1"></i> Dispatch Toast</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Safe System Audio Controls</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="triggerSystemAction('mute')"><i class="bi bi-volume-mute me-1"></i> Mute</button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="triggerSystemAction('unmute')"><i class="bi bi-volume-up me-1"></i> Unmute</button>
                            <button class="btn btn-outline-info btn-sm" onclick="triggerSystemAction('volume_up')"><i class="bi bi-plus-circle me-1"></i> Vol +10%</button>
                            <button class="btn btn-outline-info btn-sm" onclick="triggerSystemAction('volume_down')"><i class="bi bi-dash-circle me-1"></i> Vol -10%</button>
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

function apiFetch(path, opts = {}) {
    return fetch(API_BASE + path, {
        ...opts,
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + TOKEN, ...(opts.headers || {}) }
    }).then(r => r.json());
}

function loadDesktopStatus() {
    apiFetch('/desktop/status').then(res => {
        if (!res.success) return;
        const d = res.data;
        const w = d.active_window || {};
        const s = d.system_info || {};

        document.getElementById('metricAppName').textContent = w.application_name || 'VS Code';
        document.getElementById('diagTitle').textContent = w.window_title || 'ATOM Workspace';
        document.getElementById('diagApp').textContent = w.application_name || 'VS Code';
        document.getElementById('diagPlatform').textContent = w.platform || 'Windows';

        if (s.battery) {
            document.getElementById('metricBattery').textContent = `${s.battery.level_percent}% (${s.battery.power_source})`;
        }
        if (s.volume) {
            document.getElementById('diagVolume').textContent = `${s.volume.level}% (${s.volume.is_muted ? 'Muted' : 'Unmuted'})`;
        }
        if (d.developer_tools) {
            document.getElementById('metricProcesses').textContent = `${d.developer_tools.total_detected || 7} ACTIVE`;
        }
    }).catch(() => {});
}

function analyzeClipboardLive() {
    const text = document.getElementById('clipboardInputArea').value;
    if (!text.trim()) {
        document.getElementById('clipboardTypeBadge').textContent = 'EMPTY';
        document.getElementById('clipboardSummary').textContent = 'Clipboard buffer is empty.';
        document.getElementById('suggestedActionsArea').innerHTML = '<span class="text-muted small">No actions available.</span>';
        return;
    }

    apiFetch('/desktop/clipboard/analyze', {
        method: 'POST',
        body: JSON.stringify({ content: text })
    }).then(res => {
        if (!res.success) return;
        const d = res.data;
        document.getElementById('clipboardTypeBadge').textContent = d.type.toUpperCase();
        document.getElementById('clipboardSummary').textContent = d.summary;

        const actions = d.suggested_actions || [];
        document.getElementById('suggestedActionsArea').innerHTML = actions.map(act => `
            <button class="btn btn-outline-info btn-sm" onclick="alert('Executing action: ${act.label}')">
                <i class="bi bi-${act.icon} me-1"></i> ${act.label}
            </button>
        `).join('');
    });
}

function sendCustomNotification() {
    const msg = document.getElementById('notifyMessageInput').value.trim() || 'Notification from ATOM';
    apiFetch('/desktop/notify', {
        method: 'POST',
        body: JSON.stringify({ title: 'ATOM Assistant', message: msg, category: 'info' })
    }).then(res => {
        if (res.success) {
            alert('Toast notification dispatched successfully!');
        }
    });
}

function sendTestNotification() {
    apiFetch('/desktop/notify', {
        method: 'POST',
        body: JSON.stringify({ title: 'ATOM Desktop Sidecar', message: 'Sidecar pulse and window hooks verified.', category: 'system' })
    }).then(res => {
        if (res.success) {
            alert('Test notification dispatched!');
        }
    });
}

function triggerSystemAction(action) {
    apiFetch('/desktop/action', {
        method: 'POST',
        body: JSON.stringify({ action: action })
    }).then(res => {
        if (res.success) {
            loadDesktopStatus();
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    loadDesktopStatus();
    setInterval(loadDesktopStatus, 15000);
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
