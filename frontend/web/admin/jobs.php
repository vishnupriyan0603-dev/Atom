<?php
$pageTitle = "Background Jobs Queue";
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Atom Admin - Background Jobs</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-[#0b0f17] text-gray-100 min-h-screen flex">
  <?php include __DIR__ . '/components/sidebar.php'; ?>

  <main class="flex-1 p-8 overflow-y-auto">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Background Jobs Queue</h1>
        <p class="text-sm text-gray-400 mt-1">Asynchronous task execution, retry counters, and background worker queue</p>
      </div>
      <div class="flex gap-2">
        <button onclick="processNextJob()" class="px-4 py-2 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-xl text-xs font-semibold hover:bg-indigo-500/20 transition-all">
          Trigger Worker
        </button>
        <button onclick="loadJobs()" class="px-4 py-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-xl text-xs font-semibold hover:bg-emerald-500/20 transition-all">
          Refresh Queue
        </button>
      </div>
    </div>

    <!-- Jobs Table -->
    <div class="bg-[#131924] border border-[#1e2736] rounded-2xl overflow-hidden shadow-xl">
      <div class="px-6 py-4 border-b border-[#1e2736] flex items-center justify-between">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Queue Tasks</h3>
        <select id="jobStatusFilter" onchange="loadJobs()" class="bg-[#0b0f17] border border-[#1e2736] text-xs text-gray-300 rounded-lg px-3 py-1.5 focus:outline-none">
          <option value="">Status: All</option>
          <option value="queued">Status: Queued</option>
          <option value="running">Status: Running</option>
          <option value="completed">Status: Completed</option>
          <option value="failed">Status: Failed</option>
        </select>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300">
          <thead class="bg-[#0e131d] text-xs uppercase text-gray-400 border-b border-[#1e2736]">
            <tr>
              <th class="px-6 py-3.5">ID</th>
              <th class="px-6 py-3.5">Job Type</th>
              <th class="px-6 py-3.5">Status</th>
              <th class="px-6 py-3.5">Attempts</th>
              <th class="px-6 py-3.5">Created At</th>
              <th class="px-6 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody id="jobsTableBody" class="divide-y divide-[#1e2736]">
            <tr>
              <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-xs">Loading background jobs...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>
    async function loadJobs() {
      const filter = document.getElementById('jobStatusFilter').value;
      const tbody = document.getElementById('jobsTableBody');
      try {
        const url = `/api/v1/jobs${filter ? '?status=' + filter : ''}`;
        const res = await fetch(url);
        const json = await res.json();
        const data = json.data || [];

        if (data.length === 0) {
          tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 text-xs">No background jobs found</td></tr>`;
          return;
        }

        tbody.innerHTML = data.map(job => {
          const statusBadge = job.status === 'completed'
            ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">COMPLETED</span>'
            : (job.status === 'failed' ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">FAILED</span>' 
            : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">QUEUED</span>');

          return `
            <tr class="hover:bg-[#18202c]">
              <td class="px-6 py-4 text-xs font-mono text-gray-400">#${job.id}</td>
              <td class="px-6 py-4 font-medium text-white font-mono text-xs">${job.type}</td>
              <td class="px-6 py-4">${statusBadge}</td>
              <td class="px-6 py-4 text-xs text-gray-400">${job.attempts} / ${job.max_attempts}</td>
              <td class="px-6 py-4 text-xs text-gray-500">${job.created_at || '—'}</td>
              <td class="px-6 py-4 text-right">
                ${job.status === 'failed' ? `<button onclick="retryJob(${job.id})" class="px-2.5 py-1 bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 rounded-lg text-xs font-semibold">Retry</button>` : ''}
                ${job.status === 'queued' ? `<button onclick="cancelJob(${job.id})" class="px-2.5 py-1 bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 rounded-lg text-xs font-semibold">Cancel</button>` : ''}
              </td>
            </tr>
          `;
        }).join('');
      } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-rose-400 text-xs">Failed to load jobs</td></tr>`;
      }
    }

    async function processNextJob() {
      await fetch('/api/v1/jobs/process-next', { method: 'POST' });
      loadJobs();
    }

    async function retryJob(id) {
      await fetch(`/api/v1/jobs/${id}/retry`, { method: 'POST' });
      loadJobs();
    }

    async function cancelJob(id) {
      await fetch(`/api/v1/jobs/${id}/cancel`, { method: 'POST' });
      loadJobs();
    }

    document.addEventListener('DOMContentLoaded', loadJobs);
  </script>
</body>
</html>
