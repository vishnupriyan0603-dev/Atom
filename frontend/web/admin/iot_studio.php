<?php
// ATOM Web Admin — Phase 75: Autonomous Edge IoT Device Telemetry Ingest & Anomaly Watchdog Mesh
$pageTitle = "IoT Watchdog Mesh (Phase 75)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #06B6D4;">Edge IoT Telemetry &amp; Anomaly Watchdog Mesh</h2>
        <p class="text-muted small mb-0">Phase 75: High-Frequency Sensor Stream Ingestion, Multi-Metric Z-Score Anomaly Detection &amp; Fleet State Monitor</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-info text-dark fw-bold" onclick="simulateHighTempTelemetry()">
            <i class="bi bi-thermometer-high me-1"></i> Ingest Critical Anomaly (92°C)
        </button>
    </div>
</div>

<!-- Fleet Health Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">FLEET HEALTH</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricFleetHealth" style="color: #34D399;">100.0% OPERATIONAL</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TOTAL EDGE NODES</div>
            <div class="fs-4 fw-bold text-cyan-400" id="metricNodesCount">3 NODES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ACTIVE ANOMALIES</div>
            <div class="fs-4 fw-bold text-warning" id="metricAlertsCount">0 ANOMALIES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MESH INGESTION</div>
            <div class="fs-4 fw-bold text-emerald-400" style="color: #34D399;">Active (Streaming)</div>
        </div>
    </div>
</div>

<!-- Active Fleet Devices Table & Alert Stream -->
<div class="row g-4 mb-4">
    <div class="col-md-8">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-6"><i class="bi bi-cpu-fill me-2 text-cyan-400"></i>Connected Edge IoT Fleet</span>
                <span class="badge bg-secondary" id="fleetBadge">3 NODES</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-hover mb-0 align-middle small">
                        <thead class="table-secondary text-uppercase text-muted">
                            <tr>
                                <th>Device ID</th>
                                <th>Type</th>
                                <th>Max Temp Limit</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="devicesTableBody">
                            <tr><td colspan="4" class="text-center p-3 text-muted">Loading IoT fleet devices...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-warning"><i class="bi bi-bell-fill me-2"></i>Live Telemetry Ingest Sandbox</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">SELECT EDGE DEVICE</label>
                    <select id="deviceSelect" class="form-select bg-black text-white border-secondary small">
                        <option value="edge_node_01">edge_node_01</option>
                        <option value="edge_node_02">edge_node_02</option>
                        <option value="edge_node_03">edge_node_03</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">TEMPERATURE (°C)</label>
                    <input type="number" id="tempInput" class="form-control bg-black text-white border-secondary small" value="48.5" step="0.5">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">BATTERY VOLTAGE (V)</label>
                    <input type="number" id="voltageInput" class="form-control bg-black text-white border-secondary small" value="3.8" step="0.1">
                </div>

                <button class="btn btn-sm btn-cyan text-dark fw-bold w-100 mb-3" style="background: #06B6D4;" onclick="ingestCustomTelemetry()">
                    <i class="bi bi-send-fill me-1"></i> Send Telemetry Sample
                </button>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted">
                    <i class="bi bi-shield-check text-cyan-400 me-1"></i> The autonomous watchdog automatically detects sensor drift, voltage sags, and hardware thermal throttling.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadFleetStatus() {
    try {
        const res = await apiFetch('/network/iot/fleet-status');
        if (res && res.success) {
            const d = res.data;
            document.getElementById('metricFleetHealth').innerText = `${d.fleet_health_pct}% HEALTHY`;
            document.getElementById('metricNodesCount').innerText = `${d.total_devices} NODES`;
            document.getElementById('metricAlertsCount').innerText = `${d.active_alerts_count} ANOMALIES`;
            document.getElementById('fleetBadge').innerText = `${d.total_devices} NODES`;

            renderDevicesTable(d.devices || []);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderDevicesTable(devices) {
    const tbody = document.getElementById('devicesTableBody');
    if (!devices || devices.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted p-3">No devices registered.</td></tr>`;
        return;
    }

    tbody.innerHTML = devices.map(dev => `
        <tr>
            <td class="fw-bold text-white"><i class="bi bi-hdd-network text-cyan-400 me-1"></i>${escapeHtml(dev.device_id)}</td>
            <td><span class="badge bg-secondary">${escapeHtml(dev.device_type)}</span></td>
            <td>${dev.thresholds ? dev.thresholds.max_temp_c : 85}°C</td>
            <td><span class="badge ${dev.status.includes('HEALTHY') || dev.status === 'ONLINE' ? 'bg-success' : 'bg-danger'}">${escapeHtml(dev.status)}</span></td>
        </tr>
    `).join('');
}

async function ingestCustomTelemetry() {
    const devId = document.getElementById('deviceSelect').value;
    const temp = parseFloat(document.getElementById('tempInput').value);
    const voltage = parseFloat(document.getElementById('voltageInput').value);

    try {
        const res = await apiFetch('/network/iot/ingest', {
            method: 'POST',
            body: JSON.stringify({
                device_id: devId,
                metrics: { temp_c: temp, voltage_v: voltage, vibration_g: 0.2 }
            })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') {
                const isWarn = res.data.anomalies_detected > 0;
                showToast(`Telemetry ingested: ${res.data.status}`, isWarn ? 'warning' : 'success');
            }
            loadFleetStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Ingest error: ' + e.message, 'error');
    }
}

async function simulateHighTempTelemetry() {
    document.getElementById('tempInput').value = 92.0;
    ingestCustomTelemetry();
}

document.addEventListener('DOMContentLoaded', () => {
    loadFleetStatus();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
