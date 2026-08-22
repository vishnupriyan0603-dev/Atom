<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Documents & PDFs</title>
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
      <div>
        <h1 class="text-3xl font-black tracking-tight">KNOWLEDGE DOCUMENTS</h1>
        <p class="text-xs text-gray-500 mt-1">Upload reference materials (PDF, TXT, MD) to feed ATOM's context library.</p>
      </div>

      <!-- Main Uploader block -->
      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-8 text-center max-w-xl mx-auto shadow-lg space-y-4 hover:border-emerald-500/30 transition-all">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto text-2xl font-bold">
          &uarr;
        </div>
        <div>
          <h3 class="font-bold text-white text-base">Drag & Drop your document here</h3>
          <p class="text-xs text-gray-500 mt-1">Supports PDF, TXT, MD. Max size 20MB.</p>
        </div>
        <form id="uploadForm" enctype="multipart/form-data">
          <input type="file" name="doc_file" id="documentFile" class="hidden" accept=".pdf,.txt,.md" onchange="uploadDocument()">
          <button type="button" onclick="window.document.getElementById('documentFile').click()" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-white shadow shadow-emerald-500/10 transition-all">
            Browse Files
          </button>
        </form>
        <div id="uploadProgressContainer" class="hidden space-y-2">
          <div class="w-full bg-[#080a0d] rounded-full h-1.5 overflow-hidden">
            <div id="uploadProgressBar" class="bg-emerald-500 h-1.5 w-0 transition-all"></div>
          </div>
          <span id="uploadProgressText" class="text-[10px] text-gray-500">Uploading: 0%</span>
        </div>
      </div>

      <!-- Loaded documents list -->
      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl overflow-hidden shadow-lg">
        <div class="p-6 border-b border-[#1e2838]">
          <h3 class="font-bold text-white text-sm">Indexed Source Materials</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b border-[#1e2838] bg-[#0c0f14]/50 text-gray-500 font-bold">
                <th class="p-4">Document Title</th>
                <th class="p-4">Filename</th>
                <th class="p-4">Pages/Chunks</th>
                <th class="p-4 text-right">Indexed Date</th>
                <th class="p-4 text-center">Actions</th>
              </tr>
            </thead>
            <tbody id="documentList" class="text-gray-300 divide-y divide-[#1e2838]/30">
              <tr>
                <td colspan="5" class="p-8 text-center text-gray-500">No documents indexed yet. Upload files to populate knowledge.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script src="/admin/js/shared.js"></script>
  <script>
    async function loadDocuments() {
      try {
        const json = await apiFetch('/knowledge/documents');
        const tbody = document.getElementById('documentList');

        if (json.success && json.data && json.data.length > 0) {
          tbody.innerHTML = json.data.map(doc => `
            <tr class="hover:bg-[#16202e]/30 transition-all">
              <td class="p-4 font-bold text-white">${escapeHtml(doc.title || doc.filename)}${doc.trained_at ? ' <span class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-black bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 uppercase">Trained</span>' : ''}</td>
              <td class="p-4 text-gray-400 font-mono">${escapeHtml(doc.filename)}</td>
              <td class="p-4 text-emerald-400 font-semibold">${doc.path ? 'Active RAG' : 'N/A'}</td>
              <td class="p-4 text-right text-gray-500">${new Date(doc.created_at || Date.now()).toLocaleDateString()}</td>
              <td class="p-4 text-center">
                <button onclick="trainDocument(${doc.id})" title="Read this PDF with AI and teach Atom its content"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 hover:border-emerald-500/60 hover:text-emerald-300 transition-all">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
                  Train
                </button>
                <button onclick="deleteDocument(${doc.id})" title="Delete document"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-500/10 border border-red-500/30 text-red-400 hover:bg-red-500/20 hover:border-red-500/60 hover:text-red-300 transition-all ml-1">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                  Delete
                </button>
              </td>
            </tr>
          `).join('');
        } else {
          tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-gray-500">No documents indexed yet. Upload files to populate knowledge.</td></tr>';
        }
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-red-400">Failed to load documents.</td></tr>';
      }
    }

    async function trainDocument(id) {
      showToast('Reading PDF page-by-page with AI and training Atom...', 'info');
      try {
        const json = await apiFetch('/knowledge/documents/' + id + '/train', { method: 'POST' });
        if (json.success) {
          const pagesRead = json.data && json.data.pages_read ? ' (' + json.data.pages_read + ' pages read)' : '';
          showToast((json.message || 'Document trained successfully') + pagesRead, 'success');
          if (json.data && json.data.ai_summary) {
            showUnderstandingModal(json.data.ai_summary);
          }
          loadDocuments();
        } else {
          showToast(json.message || 'Training failed', 'error');
        }
      } catch (e) {
        showToast('Training failed. Verify backend services.', 'error');
      }
    }

    function showUnderstandingModal(summary) {
      const overlay = document.createElement('div');
      overlay.id = 'understandingModal';
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(4,5,8,0.85);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;z-index:9999;';
      overlay.innerHTML = `
        <div style="width:640px;max-width:92%;max-height:80vh;overflow-y:auto;background:#11151c;border:1px solid #1e2838;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,0.6);">
          <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid #1e2838;">
            <h3 style="font-size:15px;font-weight:800;color:#fff;margin:0;">ATOM'S UNDERSTANDING</h3>
            <button onclick="document.getElementById('understandingModal').remove()" style="background:none;border:none;color:#8b93a1;cursor:pointer;font-size:20px;line-height:1;">&times;</button>
          </div>
          <div style="padding:24px;color:#cbd5e1;font-size:12.5px;line-height:1.7;white-space:pre-wrap;">${escapeHtml(summary)}</div>
        </div>`;
      document.body.appendChild(overlay);
      overlay.addEventListener('click', function(e) { if (e.target === overlay) overlay.remove(); });
    }

    async function deleteDocument(id) {
      if (!confirm('Are you sure you want to delete this document and its indexed chunks?')) return;
      try {
        const json = await apiFetch('/knowledge/documents/' + id, { method: 'DELETE' });
        if (json.success) {
          showToast(json.message || 'Document deleted successfully', 'success');
          loadDocuments();
        } else {
          showToast(json.message || 'Unknown error', 'error');
        }
      } catch (e) {
        showToast('Delete failed. Verify backend services.', 'error');
      }
    }

    async function uploadDocument() {
      const fileInput = document.getElementById('documentFile');
      if (fileInput.files.length === 0) return;

      const file = fileInput.files[0];
      const formData = new FormData();
      formData.append('doc_file', file); // Maps to knowledge/upload endpoint

      const progContainer = document.getElementById('uploadProgressContainer');
      const bar = document.getElementById('uploadProgressBar');
      const txt = document.getElementById('uploadProgressText');

      progContainer.classList.remove('hidden');
      bar.style.width = '20%';
      txt.textContent = 'Uploading and processing RAG chunks...';

      try {
        // Multipart upload: set the Authorization header manually (FormData sets its own content-type).
        const resp = await fetch(ATOM_API + '/knowledge/upload', {
          method: 'POST',
          headers: { 'Authorization': 'Bearer ' + getAuthToken() },
          body: formData
        });
        const json = await resp.json();

        if (resp.status === 401) {
          handleAuthFailure();
          return;
        }

        if (json.success) {
          bar.style.width = '100%';
          txt.textContent = 'Completed!';
          showToast(json.message || 'Document processed and imported successfully', 'success');
          loadDocuments();
        } else {
          showToast('Upload failed: ' + (json.message || 'Unknown error'), 'error');
        }
      } catch (e) {
        showToast('File upload failed. Verify backend services.', 'error');
      } finally {
        setTimeout(() => {
          progContainer.classList.add('hidden');
          bar.style.width = '0%';
        }, 2000);
      }
    }

    loadDocuments();
  </script>
</body>
</html>
