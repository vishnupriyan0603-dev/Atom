<?php
$pageTitle = "Human Approval Gate";
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Atom Admin - Human Approvals</title>
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
        <h1 class="text-2xl font-bold text-white tracking-tight">Human Approval Gate</h1>
        <p class="text-sm text-gray-400 mt-1">Authorize or reject high-risk tool executions and candidate model promotions</p>
      </div>
      <button onclick="loadApprovals()" class="px-4 py-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-xl text-xs font-semibold hover:bg-emerald-500/20 transition-all">
        Refresh List
      </button>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-[#131924] border border-[#1e2736] rounded-2xl p-5">
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pending Requests</span>
        <div id="pendingCount" class="text-3xl font-extrabold text-amber-400 mt-2">0</div>
      </div>
      <div class="bg-[#131924] border border-[#1e2736] rounded-2xl p-5">
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Approved Requests</span>
        <div id="approvedCount" class="text-3xl font-extrabold text-emerald-400 mt-2">0</div>
      </div>
      <div class="bg-[#131924] border border-[#1e2736] rounded-2xl p-5">
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Rejected Requests</span>
        <div id="rejectedCount" class="text-3xl font-extrabold text-rose-400 mt-2">0</div>
      </div>
    </div>

    <!-- Approvals Table -->
    <div class="bg-[#131924] border border-[#1e2736] rounded-2xl overflow-hidden shadow-xl">
      <div class="px-6 py-4 border-b border-[#1e2736] flex items-center justify-between">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Approval Requests</h3>
        <select id="statusFilter" onchange="loadApprovals()" class="bg-[#0b0f17] border border-[#1e2736] text-xs text-gray-300 rounded-lg px-3 py-1.5 focus:outline-none">
          <option value="pending">Status: Pending</option>
          <option value="approved">Status: Approved</option>
          <option value="rejected">Status: Rejected</option>
          <option value="">Status: All</option>
        </select>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-300">
          <thead class="bg-[#0e131d] text-xs uppercase text-gray-400 border-b border-[#1e2736]">
            <tr>
              <th class="px-6 py-3.5">ID</th>
              <th class="px-6 py-3.5">Tool / Target</th>
              <th class="px-6 py-3.5">Action</th>
              <th class="px-6 py-3.5">Risk Level</th>
              <th class="px-6 py-3.5">Reason</th>
              <th class="px-6 py-3.5">Status</th>
              <th class="px-6 py-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody id="approvalsTableBody" class="divide-y divide-[#1e2736]">
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-gray-500 text-xs">Loading approval requests...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <script>
    async function loadApprovals() {
      const filter = document.getElementById('statusFilter').value;
      const tbody = document.getElementById('approvalsTableBody');
      try {
        const url = `/api/v1/approvals${filter ? '?status=' + filter : ''}`;
        const res = await fetch(url);
        const json = await res.json();
        const data = json.data || [];

        let pending = 0, approved = 0, rejected = 0;

        if (data.length === 0) {
          tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500 text-xs">No approval requests found</td></tr>`;
          return;
        }

        tbody.innerHTML = data.map(item => {
          if (item.status === 'pending') pending++;
          if (item.status === 'approved') approved++;
          if (item.status === 'rejected') rejected++;

          const riskBadge = item.risk_level === 'high' 
            ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">HIGH</span>'
            : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">MEDIUM</span>';

          const statusBadge = item.status === 'approved'
            ? '<span class="text-emerald-400 font-semibold">Approved</span>'
            : (item.status === 'rejected' ? '<span class="text-rose-400 font-semibold">Rejected</span>' : '<span class="text-amber-400 font-semibold">Pending</span>');

          return `
            <tr class="hover:bg-[#18202c]">
              <td class="px-6 py-4 text-xs font-mono text-gray-400">#${item.id}</td>
              <td class="px-6 py-4 font-medium text-white">${item.tool_name || item.target_component || 'System'}</td>
              <td class="px-6 py-4 text-xs text-gray-300 font-mono">${item.action || 'EXECUTE'}</td>
              <td class="px-6 py-4">${riskBadge}</td>
              <td class="px-6 py-4 text-xs text-gray-400">${item.reason || ''}</td>
              <td class="px-6 py-4 text-xs">${statusBadge}</td>
              <td class="px-6 py-4 text-right">
                ${item.status === 'pending' ? `
                  <button onclick="approveReq(${item.id})" class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 rounded-lg text-xs font-semibold mr-1">Approve</button>
                  <button onclick="rejectReq(${item.id})" class="px-2.5 py-1 bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 rounded-lg text-xs font-semibold">Reject</button>
                ` : '<span class="text-xs text-gray-600">—</span>'}
              </td>
            </tr>
          `;
        }).join('');

        document.getElementById('pendingCount').textContent = pending;
        document.getElementById('approvedCount').textContent = approved;
        document.getElementById('rejectedCount').textContent = rejected;

      } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-rose-400 text-xs">Failed to load approvals</td></tr>`;
      }
    }

    async function approveReq(id) {
      if (!confirm(`Grant approval for request #${id}?`)) return;
      await fetch(`/api/v1/approvals/${id}/approve`, { method: 'POST' });
      loadApprovals();
    }

    async function rejectReq(id) {
      if (!confirm(`Reject request #${id}?`)) return;
      await fetch(`/api/v1/approvals/${id}/reject`, { method: 'POST' });
      loadApprovals();
    }

    document.addEventListener('DOMContentLoaded', loadApprovals);
  </script>
</body>
</html>
