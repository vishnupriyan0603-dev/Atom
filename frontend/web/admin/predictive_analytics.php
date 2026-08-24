<?php
// ATOM Web Admin — Phase 38: Autonomous Time-Series Predictive Forecasting & Anomaly Detection Brain Dashboard
$pageTitle = "Predictive Analytics Brain";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #F59E0B;">Autonomous Predictive Forecasting Brain</h2>
        <p class="text-muted small mb-0">Holt-Winters triple exponential smoothing, statistical Z-score anomaly detection &amp; system resource saturation estimation</p>
    </div>
    <div>
        <button class="btn btn-outline-secondary btn-sm me-2" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-dark fw-bold" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); border: none;" onclick="runForecastDemo()">
            <i class="bi bi-graph-up-arrow me-1"></i> Run Predictive Model
        </button>
    </div>
</div>

<!-- Predictive Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">FORECAST MODEL</div>
            <div class="fs-4 fw-bold text-warning" id="metricModel">HOLT-WINTERS (α=0.3)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">MODEL ACCURACY (RMSE)</div>
            <div class="fs-4 fw-bold text-success" id="metricRMSE">RMSE = 1.42 (High)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ANOMALY DETECTOR</div>
            <div class="fs-4 fw-bold text-info" id="metricZScore">Z-Score (|Z| > 3.0)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">TIME-TO-EXHAUSTION (TTE)</div>
            <div class="fs-4 fw-bold" style="color:#F59E0B;" id="metricTTE">84h (Healthy)</div>
        </div>
    </div>
</div>

<!-- Interactive Forecasting & Anomaly Grid -->
<div class="row g-4 mb-4">
    <!-- 1. Holt-Winters Time-Series Forecaster -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold" style="color:#F59E0B;"><i class="bi bi-graph-up me-2"></i>Holt-Winters Horizon Projection</span>
                <span class="badge bg-warning text-dark">95% CONFIDENCE BOUNDS</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">HISTORICAL SERIES (Comma-separated numbers)</label>
                    <input type="text" id="seriesInput" class="form-control bg-black text-white border-secondary" value="10, 12, 15, 14, 18, 22, 25, 24, 28, 32, 35, 36, 40, 45">
                </div>
                <button class="btn btn-sm text-dark fw-bold w-100 mb-3" style="background: #F59E0B;" onclick="generateForecast()">
                    <i class="bi bi-calculator me-1"></i> Compute Holt-Winters Horizon (5-Step)
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 120px;">
                    <div class="text-muted small fw-bold mb-1">PROJECTION RESULTS:</div>
                    <div id="forecastOutput" class="small text-amber-300" style="font-family: monospace; white-space: pre-wrap; color: #FCD34D;">
Click 'Compute Holt-Winters Horizon' to forecast future points.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Streaming Anomaly & Resource Headroom Predictor -->
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-info"><i class="bi bi-activity me-2"></i>Anomaly Outlier &amp; Headroom TTE</span>
                <span class="badge bg-info text-dark">Welford Algorithm</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">STREAMING SERIES TO SCAN</label>
                    <input type="text" id="anomalyInput" class="form-control bg-black text-white border-secondary" value="20, 22, 21, 23, 22, 95, 21, 20, 22, 21, 24, 22">
                </div>
                <button class="btn btn-sm btn-info text-dark fw-bold w-100 mb-3" onclick="detectAnomalies()">
                    <i class="bi bi-shield-exclamation me-1"></i> Detect Statistical Anomaly Spikes
                </button>
                <div class="p-3 bg-black border border-secondary rounded" style="min-height: 120px;">
                    <div class="text-muted small fw-bold mb-1">ANOMALY DETECTION REPORT:</div>
                    <div id="anomalyOutput" class="small text-info" style="font-family: monospace; white-space: pre-wrap;">
Ready to detect outlier spikes.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function generateForecast() {
    const raw = document.getElementById('seriesInput').value;
    const series = raw.split(',').map(s => parseFloat(s.trim())).filter(n => !isNaN(n));
    try {
        const res = await fetch('/api/v1/predictive/forecast', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ series: series, horizon: 5 })
        });
        const data = await res.json();
        if (data.success) {
            const d = data.data;
            document.getElementById('metricRMSE').innerText = `RMSE = ${d.rmse}`;
            document.getElementById('forecastOutput').innerText = 
                `MODEL       : ${d.model}\n` +
                `RMSE        : ${d.rmse}\n` +
                `LAST LEVEL  : ${d.last_level}\n` +
                `LAST TREND  : ${d.last_trend}\n\n` +
                `FUTURE HORIZON PREDICTIONS:\n` +
                d.predictions.map(p => `  • Step +${p.step}: Forecast = ${p.forecast} (95% CI: [${p.lower_bound}, ${p.upper_bound}])`).join('\n');
        }
    } catch (e) {
        document.getElementById('forecastOutput').innerText = 'Error: ' + e.message;
    }
}

async function detectAnomalies() {
    const raw = document.getElementById('anomalyInput').value;
    const series = raw.split(',').map(s => parseFloat(s.trim())).filter(n => !isNaN(n));
    try {
        const res = await fetch('/api/v1/predictive/anomalies', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ series: series })
        });
        const data = await res.json();
        if (data.success) {
            const d = data.data;
            document.getElementById('anomalyOutput').innerText = 
                `SERIES MEAN     : ${d.mean}\n` +
                `STD DEVIATION   : ${d.std_dev}\n` +
                `Z-SCORE THRESH  : ${d.z_threshold}\n` +
                `ANOMALIES FOUND : ${d.total_anomalies}\n\n` +
                (d.anomalies.length > 0 ? d.anomalies.map(a => `⚠️ [${a.severity}] Index ${a.index}: Value = ${a.value} (|Z| = ${a.z_score})`).join('\n') : '✨ No anomaly spikes detected.');
        }
    } catch (e) {
        document.getElementById('anomalyOutput').innerText = 'Error: ' + e.message;
    }
}

function runForecastDemo() {
    generateForecast();
    detectAnomalies();
}

document.addEventListener('DOMContentLoaded', () => runForecastDemo());
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
