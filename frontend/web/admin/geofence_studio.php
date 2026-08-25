<?php
// ATOM Web Admin — Phase 64: Zero-Trust IP Geolocation & Geo-Fencing Access Firewall
$pageTitle = "Geo-Fencing Firewall (Phase 64)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #EC4899;">Zero-Trust IP Geolocation &amp; Geo-Fence Firewall</h2>
        <p class="text-muted small mb-0">Phase 64: Country-Level Access Policy Mesh, CIDR Subnet Protection, IP Geolocation &amp; Real-Time Threat Border Firewall</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm text-white fw-bold" style="background: #EC4899;" onclick="evaluateIpGeoFence()">
            <i class="bi bi-shield-check me-1"></i> Test Client IP
        </button>
    </div>
</div>

<!-- GeoFence Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">FIREWALL STATUS</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricStatus" style="color: #34D399;">ACTIVE (ZERO-TRUST)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">ALLOWLISTED COUNTRIES</div>
            <div class="fs-4 fw-bold text-info">7 COUNTRIES</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">BLOCKED COUNTRIES</div>
            <div class="fs-4 fw-bold text-danger">4 JURISDICTIONS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CIDR SUBNET RULES</div>
            <div class="fs-4 fw-bold text-warning">2 SUBNETS ACTIVE</div>
        </div>
    </div>
</div>

<!-- IP Testing Sandbox & Policy View -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary">
                <span class="fw-bold text-pink-400"><i class="bi bi-globe-americas me-2"></i>Test Client IP Address Against Policy</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">CLIENT IP ADDRESS</label>
                    <input type="text" id="targetIpInput" class="form-control bg-black text-white border-secondary small" value="127.0.0.1" placeholder="e.g. 127.0.0.1 or 203.0.113.15">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">POLICY ENFORCEMENT MODE</label>
                    <select class="form-select bg-black text-white border-secondary small" id="policyModeSelect">
                        <option value="allowlist" selected>Zero-Trust Strict Allowlist (Only Allowed Countries)</option>
                        <option value="blocklist">Blocklist Only (Allow all except banned)</option>
                    </select>
                </div>

                <div class="p-3 bg-black rounded border border-secondary text-xs text-muted mb-3">
                    <i class="bi bi-info-circle text-pink-400 me-1"></i> Phase 64 Geo-Fence intercepts unauthorized international traffic before reaching application logic or database queries.
                </div>

                <button class="btn btn-sm text-white fw-bold w-100" style="background: #EC4899;" onclick="evaluateIpGeoFence()">
                    <i class="bi bi-shield-lock me-1"></i> Evaluate Geo-Fence Policy
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card bg-dark border-secondary text-white h-100">
            <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                <span class="fw-bold text-success"><i class="bi bi-fingerprint me-2"></i>Evaluation Result &amp; Geo Metadata</span>
                <span class="badge bg-success" id="accessBadge">ACCESS GRANTED</span>
            </div>
            <div class="card-body">
                <div class="p-3 bg-black rounded border border-secondary mb-3">
                    <div class="d-flex justify-content-between text-xs mb-2">
                        <span class="text-muted fw-bold">RESOLVED COUNTRY:</span>
                        <span class="text-white fw-bold" id="resCountry">India (IN)</span>
                    </div>
                    <div class="d-flex justify-content-between text-xs mb-2">
                        <span class="text-muted fw-bold">RESOLVED CITY / LAT-LON:</span>
                        <span class="text-white fw-bold" id="resCity">Chennai (13.08, 80.27)</span>
                    </div>
                    <div class="d-flex justify-content-between text-xs mb-2">
                        <span class="text-muted fw-bold">EVALUATION REASON:</span>
                        <span class="text-emerald-400 fw-bold" id="resReason" style="color: #34D399;">ACCESS_GRANTED_LOCAL_NETWORK</span>
                    </div>
                </div>

                <div class="text-muted small fw-bold mb-2">ACTIVE COUNTRY ALLOWLIST:</div>
                <div class="d-flex flex-wrap gap-1">
                    <span class="badge bg-secondary">🇮🇳 IN (India)</span>
                    <span class="badge bg-secondary">🇺🇸 US (United States)</span>
                    <span class="badge bg-secondary">🇬🇧 GB (United Kingdom)</span>
                    <span class="badge bg-secondary">🇩🇪 DE (Germany)</span>
                    <span class="badge bg-secondary">🇸🇬 SG (Singapore)</span>
                    <span class="badge bg-secondary">🇨🇦 CA (Canada)</span>
                    <span class="badge bg-secondary">🇦🇺 AU (Australia)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function evaluateIpGeoFence() {
    const ip = document.getElementById('targetIpInput').value.trim();
    const mode = document.getElementById('policyModeSelect').value;

    try {
        const res = await apiFetch('/security/geofence/evaluate', {
            method: 'POST',
            body: JSON.stringify({ ip: ip, mode: mode })
        });

        if (res && res.success) {
            const d = res.data;
            const badge = document.getElementById('accessBadge');
            if (d.allowed) {
                badge.className = 'badge bg-success';
                badge.innerText = 'ACCESS GRANTED';
                document.getElementById('resReason').innerText = d.reason;
                document.getElementById('resReason').className = 'text-emerald-400 fw-bold';
            } else {
                badge.className = 'badge bg-danger';
                badge.innerText = 'GEO-FENCE BLOCKED';
                document.getElementById('resReason').innerText = d.reason;
                document.getElementById('resReason').className = 'text-danger fw-bold';
            }

            if (d.geo) {
                document.getElementById('resCountry').innerText = `${d.geo.country_name} (${d.geo.country_code})`;
                document.getElementById('resCity').innerText = `${d.geo.city} (${d.geo.lat}, ${d.geo.lon})`;
            }

            if (typeof showToast === 'function') {
                showToast(d.allowed ? 'IP passed geo-fence access policy!' : 'IP blocked by zero-trust geo-fence policy!', d.allowed ? 'success' : 'error');
            }
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('GeoFence error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    evaluateIpGeoFence();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
