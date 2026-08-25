<?php
require_once __DIR__ . '/../bootstrap.php';

$initialRecords = [];
if (!empty($dbConnected) && $dbConnection !== null) {
    try {
        $pdo = $dbConnection->getPdo();
        $sql = "SELECT c.id, 
                       COALESCE(NULLIF(c.section_title, ''), d.title, CONCAT('Document #', c.document_id, ' Chunk #', c.id)) AS title,
                       c.chunk_text AS content,
                       COALESCE(NULLIF(d.category, ''), 'General') AS collection,
                       c.created_at
                FROM atom_document_chunks c
                LEFT JOIN atom_documents d ON c.document_id = d.id
                ORDER BY c.id DESC
                LIMIT 200";
        $stmt = $pdo->query($sql);
        $initialRecords = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        // Table or columns might differ
    }

    if (empty($initialRecords)) {
        try {
            $stmt = $pdo->query("SELECT id, title, content, collection, created_at FROM knowledge_items ORDER BY id DESC LIMIT 200");
            $initialRecords = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {}
    }
}
$initialRecordsJson = json_encode($initialRecords ?: [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Knowledge Base</title>
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
          <div class="flex items-center gap-3">
            <h1 class="text-3xl font-black tracking-tight">KNOWLEDGE RECORDS</h1>
            <span id="recordCountBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              <?= count($initialRecords) ?> Records
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-1">Search, modify, verify, or add RAG context chunks stored in the system database.</p>
        </div>
        <div class="flex items-center gap-3">
          <a href="<?= $getAdminUrl('documents') ?>" class="px-4 py-2 rounded-xl bg-[#1e2735] hover:bg-[#283548] text-white text-xs font-bold transition flex items-center gap-2 border border-[#2a3649]">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
            Upload PDF Document
          </a>
          <button onclick="openAddModal()" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:opacity-90 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-emerald-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Knowledge Chunk
          </button>
        </div>
      </div>

      <!-- Filters & search -->
      <div class="bg-[#11151c] border border-[#1e2838] p-4 rounded-2xl flex flex-col sm:flex-row gap-4 justify-between shadow-lg items-center">
        <div class="flex flex-wrap gap-2" id="categoryButtons">
          <button onclick="setCategory('All', this)" class="cat-btn px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#1e2735] text-white">All</button>
          <button onclick="setCategory('PHP', this)" class="cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#1e2735]/55">PHP</button>
          <button onclick="setCategory('Laravel', this)" class="cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#1e2735]/55">Laravel</button>
          <button onclick="setCategory('Database', this)" class="cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#1e2735]/55">Database</button>
          <button onclick="setCategory('General', this)" class="cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#1e2735]/55">General</button>
        </div>
        <div class="relative w-full sm:w-auto">
          <input type="text" id="recordSearch" oninput="renderRecords()" placeholder="Filter records..." class="w-full sm:w-64 pl-4 pr-4 py-1.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs focus:outline-none focus:border-emerald-500/50 text-[#f0f4f8]">
        </div>
      </div>

      <!-- Records Table -->
      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl overflow-hidden shadow-lg">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-[#1e2838] bg-[#0c0f14]/50 text-gray-500 font-bold">
                <th class="p-4 w-16">ID</th>
                <th class="p-4">Excerpts / Chunks</th>
                <th class="p-4 w-32">Category</th>
                <th class="p-4 w-28 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="knowledgeList" class="text-gray-300 divide-y divide-[#1e2838]/30">
              <tr>
                <td colspan="4" class="p-8 text-center text-gray-500">Loading knowledge records...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- ADD KNOWLEDGE MODAL -->
  <div id="addModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 max-w-lg w-full space-y-4 shadow-2xl">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-bold text-white">Add Knowledge Chunk</h3>
        <button onclick="closeAddModal()" class="text-gray-400 hover:text-white text-lg">&times;</button>
      </div>
      <div class="space-y-3 text-xs">
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Title / Section</label>
          <input type="text" id="newTitle" placeholder="e.g. PHP 8.3 Readonly Classes & Attributes" class="w-full p-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500">
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Category / Collection</label>
          <select id="newCategory" class="w-full p-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500">
            <option value="PHP">PHP</option>
            <option value="Laravel">Laravel</option>
            <option value="Database">Database</option>
            <option value="General" selected>General</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Content / Excerpt</label>
          <textarea id="newContent" rows="5" placeholder="Enter knowledge content text or code snippet..." class="w-full p-2.5 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-2">
        <button onclick="closeAddModal()" class="px-4 py-2 rounded-xl bg-[#1e2735] hover:bg-[#283548] text-gray-300 text-xs font-bold transition">Cancel</button>
        <button onclick="saveKnowledgeRecord()" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:opacity-90 text-white text-xs font-bold transition">Save Chunk</button>
      </div>
    </div>
  </div>

  <script src="<?= $getBaseUrl() ?>/admin/js/shared.js"></script>
  <script>
    let allRecords = <?= $initialRecordsJson ?: '[]' ?>;
    let selectedCat = 'All';

    function escapeHtml(str) {
      if (!str) return '';
      return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    async function loadKnowledge() {
      const tbody = document.getElementById('knowledgeList');
      if (allRecords && allRecords.length > 0) {
        renderRecords();
      }

      try {
        const json = await apiFetch('/knowledge');
        if (json && json.success && Array.isArray(json.data)) {
          allRecords = json.data;
          renderRecords();
        } else if (!allRecords || allRecords.length === 0) {
          tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">No knowledge records found. Upload a document or add a chunk above.</td></tr>';
        }
      } catch (e) {
        if (!allRecords || allRecords.length === 0) {
          tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">No knowledge records found.</td></tr>';
        }
      }
    }

    function setCategory(cat, btn) {
      selectedCat = cat;
      document.querySelectorAll('.cat-btn').forEach(b => {
        b.className = 'cat-btn px-3.5 py-1.5 rounded-xl text-xs font-semibold text-gray-400 hover:text-white hover:bg-[#1e2735]/55';
      });
      if (btn) btn.className = 'cat-btn px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#1e2735] text-white';
      renderRecords();
    }

    function renderRecords() {
      const tbody = document.getElementById('knowledgeList');
      const badge = document.getElementById('recordCountBadge');
      const search = (document.getElementById('recordSearch')?.value || '').toLowerCase();

      if (badge) badge.innerText = `${allRecords.length} Records`;

      const filtered = allRecords.filter(item => {
        const catMatch = selectedCat === 'All' || (item.collection || '').toLowerCase().includes(selectedCat.toLowerCase());
        const titleMatch = (item.title || '').toLowerCase().includes(search) || (item.content || '').toLowerCase().includes(search);
        return catMatch && titleMatch;
      });

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-gray-500">No matching knowledge records found.</td></tr>';
        return;
      }

      tbody.innerHTML = filtered.map(item => `
        <tr class="hover:bg-[#16202e]/30 transition-all">
          <td class="p-4 text-gray-500 font-mono">#${item.id}</td>
          <td class="p-4">
            <div class="font-bold text-white mb-1 truncate max-w-lg">${escapeHtml(item.title || 'Untitled Chunk')}</div>
            <p class="text-[11px] text-gray-400 max-w-2xl leading-relaxed line-clamp-3">${escapeHtml(item.content || 'No excerpt available')}</p>
            ${item.created_at ? `<span class="text-[10px] text-gray-600 mt-1 block">${escapeHtml(item.created_at)}</span>` : ''}
          </td>
          <td class="p-4">
            <span class="px-2.5 py-0.5 rounded-full font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px]">
              ${escapeHtml(item.collection || 'General')}
            </span>
          </td>
          <td class="p-4 text-right">
            <button onclick="deleteRecord(${item.id})" class="text-red-400 hover:text-red-300 font-semibold px-2 py-1 rounded hover:bg-red-500/10 transition">Delete</button>
          </td>
        </tr>
      `).join('');
    }

    function openAddModal() {
      document.getElementById('addModal').classList.remove('hidden');
    }

    function closeAddModal() {
      document.getElementById('addModal').classList.add('hidden');
      document.getElementById('newTitle').value = '';
      document.getElementById('newContent').value = '';
    }

    async function saveKnowledgeRecord() {
      const title = document.getElementById('newTitle').value.trim();
      const collection = document.getElementById('newCategory').value;
      const content = document.getElementById('newContent').value.trim();

      if (!title || !content) {
        alert('Please fill in both title and content.');
        return;
      }

      try {
        const res = await apiFetch('/knowledge', {
          method: 'POST',
          body: JSON.stringify({ title, content, collection })
        });
        if (res.success || res.status === 201) {
          allRecords.unshift({
            id: res.data?.id || Date.now(),
            title,
            content,
            collection,
            created_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
          });
          closeAddModal();
          renderRecords();
          if (typeof showToast === 'function') showToast('Knowledge chunk saved successfully!', 'success');
        } else {
          alert(res.message || 'Failed to save chunk.');
        }
      } catch (e) {
        // Optimistic local add
        allRecords.unshift({
          id: Date.now(),
          title,
          content,
          collection,
          created_at: new Date().toISOString().replace('T', ' ').substring(0, 19)
        });
        closeAddModal();
        renderRecords();
      }
    }

    async function deleteRecord(id) {
      if (!confirm('Are you sure you want to delete this knowledge chunk?')) return;
      try {
        const json = await apiFetch('/knowledge/' + id, { method: 'DELETE' });
        allRecords = allRecords.filter(r => Number(r.id) !== Number(id));
        renderRecords();
        if (typeof showToast === 'function') {
          showToast('Record deleted successfully', 'success');
        }
      } catch (e) {
        allRecords = allRecords.filter(r => Number(r.id) !== Number(id));
        renderRecords();
      }
    }

    loadKnowledge();
  </script>
</body>
</html>
