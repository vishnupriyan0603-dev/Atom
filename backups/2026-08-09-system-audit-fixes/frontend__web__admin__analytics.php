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

  <script src="/admin/js/shared.js"></script>
  <script>
    // Response chart rendering
    new Chart(document.getElementById('responseChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: ['R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7', 'R8'],
        datasets: [{
          label: 'Duration (ms)',
          data: [120, 240, 180, 500, 110, 300, 420, 280],
          backgroundColor: '#10b981'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
      }
    });

    // Error chart rendering
    new Chart(document.getElementById('errorChart').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: ['Network', 'API Limit', 'Timeout', 'Validation'],
        datasets: [{
          data: [12, 19, 3, 5],
          backgroundColor: ['#ef4444', '#f59e0b', '#3b82f6', '#10b981']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
      }
    });

    async function loadToolLogs() {
      try {
        const resp = await fetch('http://localhost:8080/api/settings/tool_logs');
        const json = await resp.json();
        const tbody = document.getElementById('toolList');

        if (json.success && json.data && json.data.length > 0) {
          tbody.innerHTML = json.data.map(log => `
            <tr class="hover:bg-[#16202e]/30 transition-all">
              <td class="p-4 font-mono font-bold text-white">${log.tool_name}</td>
              <td class="p-4 text-emerald-400 font-bold">${log.duration_ms} ms</td>
              <td class="p-4"><span class="px-2 py-0.5 rounded font-bold uppercase text-[9px] bg-emerald-500/10 text-emerald-400">${log.status}</span></td>
              <td class="p-4 text-right text-gray-500">${new Date(log.created_at).toLocaleString()}</td>
            </tr>
          `).join('');
        } else {
          tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">No tool execution logs found.</td></tr>';
        }
      } catch (e) {}
    }
    loadToolLogs();
  </script>
</body>
</html>
