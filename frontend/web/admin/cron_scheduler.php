<?php
// ATOM Web Admin — Phase 49: Distributed Edge Cron Scheduler & Raft Failover Hub
$pageTitle = "Distributed Cron Scheduler (Phase 49)";
include_once __DIR__ . '/components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color: #6366F1;">Distributed Edge Cron Scheduler &amp; Raft Failover Hub</h2>
        <p class="text-muted small mb-0">Phase 49: Multi-Node Cron Job Scheduling, Raft-Style Leader Leases, Exact-Once Distributed Execution &amp; Dead-Letter Queue (DLQ) Resilience</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload();">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
        </button>
        <button class="btn btn-sm btn-primary fw-bold" onclick="renewLeaderLease()">
            <i class="bi bi-shield-check me-1"></i> Renew Raft Lease
        </button>
    </div>
</div>

<!-- Cluster & Cron Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">CLUSTER LEADER NODE</div>
            <div class="fs-4 fw-bold text-info" id="metricLeaderNode">node_alpha</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">RAFT LEASE STATUS</div>
            <div class="fs-4 fw-bold text-success" id="metricLeaseStatus">ACTIVE LEASE (30s)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">SCHEDULED JOBS</div>
            <div class="fs-4 fw-bold text-warning" id="metricJobCount">4 JOBS</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-secondary p-3 text-white">
            <div class="text-muted small fw-bold">DEAD-LETTER QUEUE (DLQ)</div>
            <div class="fs-4 fw-bold text-emerald-400" id="metricDlqCount" style="color: #34D399;">0 POISON JOBS</div>
        </div>
    </div>
</div>

<!-- Main Section: Scheduled Jobs Table & New Job Creator -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6"><i class="bi bi-clock-history me-2 text-indigo-400" style="color: #818CF8;"></i>Scheduled Distributed Cron Jobs</span>
        <button class="btn btn-sm btn-outline-info" onclick="loadCronJobs()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover mb-0 align-middle small">
                <thead class="table-secondary text-uppercase text-muted">
                    <tr>
                        <th>Job ID &amp; Task Name</th>
                        <th>Cron Schedule</th>
                        <th>Target Action</th>
                        <th>Next Execution</th>
                        <th>Status</th>
                        <th style="width: 130px;">Action</th>
                    </tr>
                </thead>
                <tbody id="cronJobsTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Loading scheduled distributed jobs...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Section 2: Create New Scheduled Task Form -->
<div class="card bg-dark border-secondary text-white mb-4">
    <div class="card-header border-secondary">
        <span class="fw-bold"><i class="bi bi-plus-circle me-2 text-success"></i>Schedule New Edge Distributed Task</span>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted small fw-bold">JOB NAME</label>
                <input type="text" id="newJobName" class="form-control bg-black text-white border-secondary" placeholder="e.g. Swarm Health Check" value="Neural Model Drift Audit">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold">CRON EXPRESSION (5-FIELD)</label>
                <input type="text" id="newJobCron" class="form-control bg-black text-white border-secondary" placeholder="*/10 * * * *" value="*/10 * * * *">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold">TARGET ACTION</label>
                <input type="text" id="newJobAction" class="form-control bg-black text-white border-secondary" placeholder="e.g. audit_model_weights" value="audit_model_drift">
            </div>
            <div class="col-md-2">
                <button class="btn btn-success fw-bold w-100" onclick="createNewCronJob()">
                    <i class="bi bi-calendar-plus me-1"></i> Schedule Job
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function loadCronJobs() {
    try {
        const res = await apiFetch('/cron/jobs');
        if (res && res.success) {
            document.getElementById('metricJobCount').innerText = `${res.data.total_jobs} JOBS`;
            renderJobsTable(res.data.jobs || []);
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Failed to load cron jobs: ' + e.message, 'error');
    }
}

async function loadClusterStatus() {
    try {
        const res = await apiFetch('/cron/cluster/status');
        if (res && res.success) {
            document.getElementById('metricLeaderNode').innerText = res.data.cluster_leader || 'Self';
            document.getElementById('metricLeaseStatus').innerText = `ACTIVE (${res.data.lease_info.lease_expires_in_seconds}s)`;
        }
    } catch (e) {
        console.error(e);
    }
}

function renderJobsTable(jobs) {
    const tbody = document.getElementById('cronJobsTableBody');
    if (!jobs || jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No scheduled jobs.</td></tr>';
        return;
    }

    tbody.innerHTML = jobs.map(j => `
        <tr>
            <td>
                <span class="fw-bold text-white">${escapeHtml(j.name)}</span>
                <span class="text-muted d-block text-xs">${escapeHtml(j.id)}</span>
            </td>
            <td><code class="text-warning">${escapeHtml(j.cron_expression)}</code></td>
            <td><span class="badge bg-secondary">${escapeHtml(j.target_action)}</span></td>
            <td>
                <span class="text-info">${escapeHtml(j.next_run_human || 'Pending')}</span>
            </td>
            <td>
                <span class="badge bg-${j.status === 'SCHEDULED' ? 'success' : (j.status === 'DEAD_LETTER_QUEUE' ? 'danger' : 'info')}">${escapeHtml(j.status)}</span>
            </td>
            <td>
                <button class="btn btn-xs btn-primary fw-bold" onclick="triggerJobNow('${escapeHtml(j.id)}')">
                    <i class="bi bi-play-fill"></i> Trigger
                </button>
            </td>
        </tr>
    `).join('');
}

async function triggerJobNow(jobId) {
    try {
        const res = await apiFetch('/cron/jobs/trigger', {
            method: 'POST',
            body: JSON.stringify({ job_id: jobId })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Job ${jobId} triggered! Status: ${res.data.execution.status}`, 'success');
            loadCronJobs();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Trigger error: ' + e.message, 'error');
    }
}

async function renewLeaderLease() {
    try {
        const res = await apiFetch('/cron/lease/renew', { method: 'POST', body: JSON.stringify({}) });
        if (res && res.success) {
            if (typeof showToast === 'function') showToast('Raft Leader Lease Renewed for 30s!', 'success');
            loadClusterStatus();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Lease renewal error: ' + e.message, 'error');
    }
}

async function createNewCronJob() {
    const name = document.getElementById('newJobName').value;
    const cron = document.getElementById('newJobCron').value;
    const action = document.getElementById('newJobAction').value;

    try {
        const res = await apiFetch('/cron/jobs', {
            method: 'POST',
            body: JSON.stringify({ name: name, cron_expression: cron, target_action: action })
        });

        if (res && res.success) {
            if (typeof showToast === 'function') showToast(`Scheduled job: ${name}`, 'success');
            loadCronJobs();
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('Job creation error: ' + e.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadCronJobs();
    loadClusterStatus();
});
</script>

<?php include_once __DIR__ . '/components/footer.php'; ?>
