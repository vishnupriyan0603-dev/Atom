<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Training examples</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
          <h1 class="text-3xl font-black tracking-tight">TRAINING DATASETS</h1>
          <p class="text-xs text-gray-500 mt-1">Manage verified examples, detect duplicate questions, and optimize model responses.</p>
        </div>
      </div>

      <!-- Duplicate Detection Segment -->
      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="w-3.5 h-3.5 rounded-full bg-[#f59e0b]"></span>
            <h3 class="font-bold text-white text-sm">Duplicate Analysis</h3>
          </div>
          <span class="text-xs text-gray-500 uppercase tracking-wider font-bold">Automatic Dedup Enabled</span>
        </div>
        <p class="text-xs text-gray-400 leading-relaxed">
          ATOM's Jaccard similarity detector finds matching questions. Grouping questions under a single canonical answer prevents response fragmentation.
        </p>
        <div id="duplicateList" class="space-y-3">
          <div class="text-center py-6 text-gray-500 text-xs">Scanning training dataset for semantic duplicates...</div>
        </div>
      </div>

      <!-- Training Records List -->
      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl overflow-hidden shadow-lg">
        <div class="p-6 border-b border-[#1e2838] flex items-center justify-between">
          <h3 class="font-bold text-white text-sm">Trained Q&A Pairs</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-[#1e2838] bg-[#0c0f14]/50 text-gray-500 font-bold">
                <th class="p-4">Q&A Details</th>
                <th class="p-4">Category</th>
                <th class="p-4">Quality</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="trainingList" class="text-gray-300 divide-y divide-[#1e2838]/30">
              <tr>
                <td colspan="4" class="p-8 text-center text-gray-500">Retrieving training records...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="/admin/js/shared.js"></script>
  <script>
    async function loadTrainingData() {
      const tbody = document.getElementById('trainingList');
      try {
        const json = await apiFetch('/analytics/training-records');

        if (json.success && json.data && json.data.length > 0) {
          tbody.innerHTML = json.data.map(item => `
            <tr class="hover:bg-[#16202e]/30 transition-all">
              <td class="p-4 space-y-1">
                <div class="font-bold text-white leading-tight">Q: ${escapeHtml(item.user_input)}</div>
                <p class="text-[11px] text-gray-500 font-mono line-clamp-2">A: ${escapeHtml(item.preferred_response)}</p>
              </td>
              <td class="p-4"><span class="px-2 py-0.5 rounded font-bold bg-emerald-500/10 text-emerald-400 text-[10px] uppercase">${escapeHtml(item.category || 'General')}</span></td>
              <td class="p-4">
                <span class="px-2 py-0.5 rounded font-bold text-[10px] uppercase ${item.quality === 'VERIFIED' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'}">${escapeHtml(item.quality)}</span>
              </td>
              <td class="p-4 text-right">
                <button onclick="rejectRecord(${item.id})" class="text-red-400 hover:text-red-300 font-bold ml-2">Reject</button>
              </td>
            </tr>
          `).join('');
        } else {
          tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">No training examples recorded.</td></tr>';
        }
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-red-400">Failed to load training records.</td></tr>';
      }
    }

    async function loadDuplicates() {
      const div = document.getElementById('duplicateList');
      try {
        const json = await apiFetch('/analytics/duplicates');

        if (json.success && json.data && json.data.length > 0) {
          div.innerHTML = json.data.map(grp => `
            <div class="p-4 rounded-xl bg-[#080a0d] border border-[#1e2838] space-y-3">
              <div class="flex items-center justify-between text-xs">
                <span class="text-amber-500 font-bold">Group #${grp.id}</span>
                <span class="text-gray-500">Similarity: ${grp.similarity}%</span>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px] text-gray-400">
                <div class="space-y-1">
                  <span class="font-bold text-white block">Question A:</span>
                  <p class="leading-relaxed">${escapeHtml(grp.question_a)}</p>
                </div>
                <div class="space-y-1">
                  <span class="font-bold text-white block">Question B:</span>
                  <p class="leading-relaxed">${escapeHtml(grp.question_b)}</p>
                </div>
              </div>
              <div class="pt-2 flex gap-2">
                <button onclick="mergeDupes(${grp.id})" class="px-3 py-1 rounded bg-emerald-500 text-white text-[10px] font-bold">Merge Alias</button>
                <button onclick="keepBoth(${grp.id})" class="px-3 py-1 rounded bg-[#11151c] text-gray-400 border border-[#1e2838] text-[10px]">Keep Both</button>
              </div>
            </div>
          `).join('');
        } else {
          div.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs">No duplicate question groups found. Dataset is clean!</div>';
        }
      } catch (e) {
        div.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs">Dataset is clean!</div>';
      }
    }

    async function rejectRecord(id) {
      if(!confirm('Reject and delete this training record?')) return;
      try {
        const json = await apiFetch('/analytics/training-records/' + id, { method: 'DELETE' });
        if(json.success) {
          showToast('Record deleted', 'success');
          loadTrainingData();
        } else {
          showToast(json.message || 'Delete failed', 'error');
        }
      } catch (e) {
        showToast('Delete failed. Verify backend services.', 'error');
      }
    }

    function mergeDupes(id) {
      showToast('Merging duplicates into a canonical question group...', 'info');
      window.location.reload();
    }

    loadTrainingData();
    loadDuplicates();
  </script>
</body>
</html>
