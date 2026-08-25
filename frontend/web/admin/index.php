<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Dashboard</title>
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
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black tracking-tight">ATOM BRAIN CONTROL CENTER</h1>
          <p class="text-xs text-gray-500 mt-1">Status: Operational. Active reasoning active.</p>
        </div>
        <div class="flex gap-2">
          <button onclick="runDeduplication()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-600 text-white shadow shadow-emerald-500/10 transition-all">
            Optimize Database
          </button>
        </div>
      </div>

      <!-- Top Score and Quick stats -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Health Score panel -->
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 flex items-center justify-between shadow-lg">
          <div>
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Brain Health</h3>
            <span class="text-4xl font-extrabold text-white mt-2 block"><?php echo $stats['health_score']; ?>%</span>
            <p class="text-[10px] text-gray-500 mt-2">Deduplicated & verified knowledge.</p>
          </div>
          <!-- Simple SVG Circular Indicator -->
          <div class="relative w-20 h-20">
            <svg class="w-full h-full transform -rotate-90">
              <circle cx="40" cy="40" r="32" stroke="#1e2838" stroke-width="6" fill="transparent" />
              <circle cx="40" cy="40" r="32" stroke="#10b981" stroke-width="6" fill="transparent"
                      stroke-dasharray="200" stroke-dashoffset="<?php echo 200 - (200 * $stats['health_score'] / 100); ?>" />
            </svg>
          </div>
        </div>

        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg">
          <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Duplicates Cleaned</h3>
          <span class="text-4xl font-extrabold text-[#f59e0b] mt-2 block"><?php echo number_format($stats['duplicate_count']); ?></span>
          <p class="text-[10px] text-gray-500 mt-2">Avoided redundant training records.</p>
        </div>

        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg">
          <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Optimized Canonical Rows</h3>
          <span class="text-4xl font-extrabold text-emerald-400 mt-2 block"><?php echo number_format($stats['optimized_count']); ?></span>
          <p class="text-[10px] text-gray-500 mt-2">Merged equivalent question groups.</p>
        </div>
      </div>

      <!-- Core RAG and stats grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-5 flex flex-col justify-between shadow">
          <span class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Knowledge Chunks</span>
          <span class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['knowledge_count']); ?></span>
        </div>
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-5 flex flex-col justify-between shadow">
          <span class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Original Documents</span>
          <span class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['document_count']); ?></span>
        </div>
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-5 flex flex-col justify-between shadow">
          <span class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Training Q&A</span>
          <span class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['training_count']); ?></span>
        </div>
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-5 flex flex-col justify-between shadow">
          <span class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">AI Chats</span>
          <span class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['conversations']); ?></span>
        </div>
      </div>

      <!-- Central status visualization and charts -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Visual brain network representation container -->
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 lg:col-span-2 shadow-lg flex flex-col justify-between">
          <div class="border-b border-[#1e2838] pb-4 mb-4">
            <h3 class="font-bold text-white text-sm">RAG & Knowledge Growth over time</h3>
          </div>
          <div class="h-64">
            <canvas id="growthChart" class="w-full h-full"></canvas>
          </div>
        </div>

        <!-- Right System detail lists -->
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg flex flex-col justify-between">
          <div class="border-b border-[#1e2838] pb-4 mb-4">
            <h3 class="font-bold text-white text-sm">Self-Learning Logs</h3>
          </div>
          <div class="flex-1 overflow-y-auto space-y-3 pr-2 text-xs" id="selfLearningLogs">
            <div class="text-center py-8 text-gray-500 text-[10px]">No learning history logged. Waiting for cross-model training cycles...</div>
          </div>
        </div>
      </div>

      <!-- ATOM SELF-LEARNING & HUMAN SAFETY GATE SECTION -->
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
              <span>🛡️</span> ATOM Safety Gate &amp; Self-Improvement Engine
            </h2>
            <p class="text-xs text-gray-400 mt-1">Autonomous flaw detection, A/B sandbox benchmarking, and mandatory human authorization gate</p>
          </div>
          <button onclick="loadSafetyGateData()" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-[#1e2838] hover:bg-[#2a384e] text-gray-300 transition">
            Refresh Safety Gate
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Pending Approvals Widget -->
          <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg lg:col-span-1 flex flex-col">
            <div class="flex items-center justify-between border-b border-[#1e2838] pb-4 mb-4">
              <h3 class="font-bold text-sky-400 text-sm flex items-center gap-2">
                <span>🛡️</span> Pending Safety Approvals
              </h3>
              <span id="pendingCountBadge" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/30">0 Pending</span>
            </div>
            <div class="flex-1 overflow-y-auto space-y-3 max-h-72" id="pendingApprovalsList">
              <div class="text-center py-6 text-gray-500 text-xs">Loading pending approvals...</div>
            </div>
          </div>

          <!-- A/B Experiments Widget -->
          <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg lg:col-span-2 flex flex-col">
            <div class="border-b border-[#1e2838] pb-4 mb-4 flex items-center justify-between">
              <h3 class="font-bold text-emerald-400 text-sm flex items-center gap-2">
                <span>🧪</span> Sandbox A/B Benchmarks &amp; Experiments
              </h3>
              <span class="text-[10px] text-gray-400">Min. Threshold: +5.0%</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs text-gray-300">
                <thead class="text-[10px] uppercase font-bold text-gray-500 border-b border-[#1e2838]">
                  <tr>
                    <th class="pb-2">Title</th>
                    <th class="pb-2">Target</th>
                    <th class="pb-2">Baseline</th>
                    <th class="pb-2">Candidate</th>
                    <th class="pb-2">Improvement</th>
                    <th class="pb-2">Status</th>
                  </tr>
                </thead>
                <tbody id="experimentsTableBody" class="divide-y divide-[#1e2838]">
                  <tr><td colspan="6" class="text-center py-6 text-gray-500">Loading experiments...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="<?= $getBaseUrl() ?>/admin/js/shared.js"></script>
  <script>
    // Growth Chart rendering
    const ctx = document.getElementById('growthChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
        datasets: [{
          label: 'Knowledge Chunks',
          data: [20, 80, 250, 450, 800, 1500, 3400, <?php echo $stats['knowledge_count']; ?>],
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.05)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: '#16202e' }, ticks: { color: '#566478' } },
          y: { grid: { color: '#16202e' }, ticks: { color: '#566478' } }
        }
      }
    });

    async function loadSelfLearningLogs() {
      const logsEl = document.getElementById('selfLearningLogs');
      try {
        const json = await apiFetch('/analytics/learning-history', { method: 'GET' });
        if (json.success && json.data && json.data.length > 0) {
          logsEl.innerHTML = json.data.map(log => `
            <div class="p-3 rounded-xl bg-[#080a0d] border border-[#1e2838] space-y-1">
              <div class="flex items-center justify-between text-[9px]">
                <span class="text-emerald-400 font-bold uppercase tracking-wider">${escapeHtml(log.topic)}</span>
                <span class="text-gray-500">${new Date(log.created_at).toLocaleTimeString()}</span>
              </div>
              <p class="text-gray-300 text-[11px] leading-relaxed">${escapeHtml(log.action_text)}</p>
            </div>
          `).join('');
        } else {
          logsEl.innerHTML = '<div class="text-center py-8 text-gray-500 text-[10px]">No learning history logged yet.</div>';
        }
      } catch (e) {
        logsEl.innerHTML = '<div class="text-center py-8 text-red-400 text-[10px]">Failed to load learning history.</div>';
      }
    }
    loadSelfLearningLogs();

    async function runDeduplication() {
      try {
        const json = await apiFetch('/analytics/optimize-training', { method: 'POST' });
        showToast(json.message || 'Optimized successfully', 'success');
        window.location.reload();
      } catch (e) {
        showToast('Optimization command failed', 'error');
      }
    }

    async function loadSafetyGateData() {
      const approvalsListEl = document.getElementById('pendingApprovalsList');
      const badgeEl = document.getElementById('pendingCountBadge');
      const expBodyEl = document.getElementById('experimentsTableBody');

      try {
        const appRes = await apiFetch('/improvement/approvals');
        if (appRes && appRes.data) {
          badgeEl.textContent = `${appRes.data.length} Pending`;
          if (appRes.data.length > 0) {
            approvalsListEl.innerHTML = appRes.data.map(item => `
              <div class="p-3.5 rounded-xl bg-[#080a0d] border border-[#1e2838] space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-sky-400">${escapeHtml(item.action)}</span>
                  <span class="text-[9px] text-gray-500">${escapeHtml(item.created_at || '')}</span>
                </div>
                <p class="text-xs text-gray-300">${escapeHtml(item.reason || 'Candidate experiment promotion')}</p>
                <div class="flex items-center justify-end gap-2 pt-1">
                  <button onclick="approveSafetyItem(${item.id})" class="px-2.5 py-1 rounded text-[11px] font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">Approve ✅</button>
                  <button onclick="rejectSafetyItem(${item.id})" class="px-2.5 py-1 rounded text-[11px] font-bold bg-rose-600 hover:bg-rose-500 text-white transition">Reject ❌</button>
                </div>
              </div>
            `).join('');
          } else {
            approvalsListEl.innerHTML = '<div class="text-center py-6 text-emerald-400 text-xs font-medium">All candidate promotions authorized!</div>';
          }
        }

        const expRes = await apiFetch('/improvement/experiments');
        if (expRes && expRes.data && expRes.data.length > 0) {
          expBodyEl.innerHTML = expRes.data.map(exp => `
            <tr class="hover:bg-[#16202e]/50 transition">
              <td class="py-2.5 font-medium text-white">${escapeHtml(exp.title)}</td>
              <td class="py-2.5 text-gray-400">${escapeHtml(exp.target_component)}</td>
              <td class="py-2.5 text-gray-300">${(exp.baseline_score * 100).toFixed(1)}%</td>
              <td class="py-2.5 text-emerald-400 font-semibold">${(exp.candidate_score * 100).toFixed(1)}%</td>
              <td class="py-2.5 text-emerald-400 font-bold">+${parseFloat(exp.improvement_pct).toFixed(1)}%</td>
              <td class="py-2.5">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold ${exp.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'}">${escapeHtml(exp.status)}</span>
              </td>
            </tr>
          `).join('');
        } else {
          expBodyEl.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-gray-500">No active or past experiments found.</td></tr>';
        }
      } catch (e) {
        approvalsListEl.innerHTML = '<div class="text-center py-6 text-red-400 text-xs">Failed to load approvals.</div>';
        expBodyEl.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-red-400">Failed to load experiments.</td></tr>';
      }
    }

    async function approveSafetyItem(id) {
      try {
        const json = await apiFetch(`/improvement/approvals/${id}/approve`, { method: 'POST', body: JSON.stringify({ approver: 'WebAdmin' }) });
        showToast(json.message || 'Approved experiment promotion', 'success');
        loadSafetyGateData();
      } catch (e) {
        showToast('Approval action failed', 'error');
      }
    }

    async function rejectSafetyItem(id) {
      try {
        const json = await apiFetch(`/improvement/approvals/${id}/reject`, { method: 'POST', body: JSON.stringify({ approver: 'WebAdmin', reason: 'Rejected from web admin UI' }) });
        showToast(json.message || 'Rejected experiment promotion', 'info');
        loadSafetyGateData();
      } catch (e) {
        showToast('Rejection action failed', 'error');
      }
    }

    loadSafetyGateData();
  </script>
</body>
</html>
