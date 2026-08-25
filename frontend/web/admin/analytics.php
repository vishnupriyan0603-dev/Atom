<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Observability Analytics</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background-color: #080a0d;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
    }
  </style>
</head>
<body class="text-[#f0f4f8] h-screen flex overflow-hidden">

  <!-- COLLAPSIBLE SIDEBAR -->
  <?php include_once __DIR__ . '/components/sidebar.php'; ?>

  <!-- MAIN WORKSPACE CONTAINER -->
  <div class="flex-1 flex flex-col overflow-hidden">
    <!-- TOP BAR -->
    <?php include_once __DIR__ . '/components/topbar.php'; ?>

    <!-- CONTENT BODY -->
    <main class="flex-1 overflow-y-auto p-8 space-y-8">
      <div>
        <h1 class="text-3xl font-black tracking-tight">BRAIN OBSERVABILITY</h1>
        <p class="text-xs text-gray-500 mt-1">Audit logs, tool execution logs, and request/response metrics.</p>
      </div>

      <!-- Main chart and analytics card -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-[#11151c] border border-[#1e2838] p-6 rounded-2xl shadow-lg space-y-4">
          <h3 class="font-bold text-white text-sm">Response Time Metrics</h3>
          <div class="h-64">
            <canvas id="responseChart" class="w-full h-full"></canvas>
          </div>
        </div>

        <div class="bg-[#11151c] border border-[#1e2838] p-6 rounded-2xl shadow-lg space-y-4">
          <h3 class="font-bold text-white text-sm">Error Distribution</h3>
          <div class="h-64">
            <canvas id="errorChart" class="w-full h-full"></canvas>
          </div>
        </div>
      </div>

      <!-- Tool executions table -->
      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl overflow-hidden shadow-lg">
        <div class="p-6 border-b border-[#1e2838]">
          <h3 class="font-bold text-white text-sm">Tool Execution Audit Logs</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-[#1e2838] bg-[#0c0f14]/50 text-gray-500 font-bold">
                <th class="p-4">Tool Name</th>
                <th class="p-4">Duration</th>
                <th class="p-4">Status</th>
                <th class="p-4 text-right">Execution Time</th>
              </tr>
            </thead>
            <tbody id="toolList" class="text-gray-300 divide-y divide-[#1e2838]/30">
              <tr>
                <td colspan="4" class="p-8 text-center text-gray-500">Loading audit history...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="<?= $getBaseUrl() ?>/admin/js/shared.js"></script>
  <script>
    // Response time + error charts driven by real atom_requests / atom_errors data.
    async function loadCharts() {
      var reqJson = await apiFetch('/analytics/requests?per_page=50', { method: 'GET' });
      var errJson = await apiFetch('/analytics/errors?per_page=100', { method: 'GET' });

      var reqs = (reqJson.success && reqJson.data) ? reqJson.data : [];
      var errs = (errJson.success && errJson.data) ? errJson.data : [];

      var labels = reqs.map(function(r) { return r.request_id || 'REQ'; });
      var durations = reqs.map(function(r) { return r.duration_ms || 0; });

      new Chart(document.getElementById('responseChart').getContext('2d'), {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Duration (ms)',
            data: durations,
            backgroundColor: '#10b981'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { x: { ticks: { font: { size: 8 }, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } } }
        }
      });

      var errCount = {};
      errs.forEach(function(e) { var c = e.category || 'Unknown'; errCount[c] = (errCount[c] || 0) + 1; });
      var errLabels = Object.keys(errCount);
      var errData = errLabels.map(function(k) { return errCount[k]; });

      new Chart(document.getElementById('errorChart').getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: errLabels.length ? errLabels : ['None'],
          datasets: [{
            data: errLabels.length ? errData : [1],
            backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#a855f7']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    }

    async function loadToolLogs() {
      const tbody = document.getElementById('toolList');
      try {
        const json = await apiFetch('/analytics/tool-logs', { method: 'GET' });

        if (json.success && json.data && json.data.length > 0) {
          tbody.innerHTML = json.data.map(log => `
            <tr class="hover:bg-[#16202e]/30 transition-all">
              <td class="p-4 font-mono font-bold text-white">${escapeHtml(log.tool_name)}</td>
              <td class="p-4 text-emerald-400 font-bold">${escapeHtml(log.duration_ms)} ms</td>
              <td class="p-4"><span class="px-2 py-0.5 rounded font-bold uppercase text-[9px] bg-emerald-500/10 text-emerald-400">${escapeHtml(log.status)}</span></td>
              <td class="p-4 text-right text-gray-500">${new Date(log.created_at).toLocaleString()}</td>
            </tr>
          `).join('');
        } else {
          tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">No tool execution logs found.</td></tr>';
        }
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-red-400">Failed to load tool logs.</td></tr>';
      }
    }

    loadCharts();
    loadToolLogs();
  </script>
</body>
</html>
