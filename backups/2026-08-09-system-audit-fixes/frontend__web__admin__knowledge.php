<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Knowledge base</title>
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
          <h1 class="text-3xl font-black tracking-tight">KNOWLEDGE RECORDS</h1>
          <p class="text-xs text-gray-500 mt-1">Search, modify, or verify RAG context chunks stored in the system database.</p>
        </div>
      </div>

      <!-- Filters & search -->
      <div class="bg-[#11151c] border border-[#1e2838] p-4 rounded-2xl flex flex-col sm:flex-row gap-4 justify-between shadow-lg">
        <div class="flex flex-wrap gap-2">
          <button class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#1e2735] text-white">All</button>
          <button class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#1e2735]/55">PHP</button>
          <button class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#1e2735]/55">Laravel</button>
          <button class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#1e2735]/55">Database</button>
        </div>
        <div class="relative">
          <input type="text" id="recordSearch" placeholder="Filter records..." class="w-64 pl-4 pr-4 py-1.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs focus:outline-none focus:border-emerald-500/50 text-[#f0f4f8]">
        </div>
      </div>

      <!-- Records Table -->
      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-[#1e2838] bg-[#0c0f14]/50 text-gray-500 font-bold">
                <th class="p-4">ID</th>
                <th class="p-4">Excerpts / Chunks</th>
                <th class="p-4">Category</th>
                <th class="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="knowledgeList" class="text-gray-300 divide-y divide-[#1e2838]/30">
              <tr>
                <td colspan="4" class="p-8 text-center text-gray-500">Retrieving database records...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="/admin/js/shared.js"></script>
  <script>
    async function loadKnowledge() {
      try {
        const resp = await fetch('http://localhost:8080/api/knowledge');
        const json = await resp.json();
        const tbody = document.getElementById('knowledgeList');

        if (json.success && json.data && json.data.length > 0) {
          tbody.innerHTML = json.data.map(item => `
            <tr class="hover:bg-[#16202e]/30 transition-all">
              <td class="p-4 text-gray-500 font-mono">#${item.id}</td>
              <td class="p-4">
                <div class="font-bold text-white mb-1 truncate max-w-lg">${item.title || 'Untitled Chunk'}</div>
                <p class="text-[11px] text-gray-500 max-w-2xl leading-relaxed">${item.content || 'No description available'}</p>
              </td>
              <td class="p-4"><span class="px-2 py-0.5 rounded font-bold uppercase bg-emerald-500/10 text-emerald-400 text-[10px]">${item.collection || 'General'}</span></td>
              <td class="p-4 text-right">
                <button onclick="deleteRecord(${item.id})" class="text-red-400 hover:text-red-300 font-bold ml-2">Delete</button>
              </td>
            </tr>
          `).join('');
        } else {
          tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">No knowledge chunks found.</td></tr>';
        }
      } catch (e) {
        // Fallback
      }
    }

    async function deleteRecord(id) {
      if(!confirm('Are you sure you want to delete this knowledge chunk?')) return;
      try {
        const resp = await fetch('http://localhost:8080/api/knowledge/' + id, { method: 'DELETE' });
        if(resp.ok) {
          showToast('Record deleted successfully', 'success');
          loadKnowledge();
        }
      } catch (e) {}
    }

    loadKnowledge();
  </script>
</body>
</html>
