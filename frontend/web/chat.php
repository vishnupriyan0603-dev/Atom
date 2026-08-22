<?php
require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM — Conversations</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body {
      background-color: #080a0d;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
    }
  </style>
</head>
<body class="text-[#f0f4f8] h-screen flex">

  <!-- COLLAPSIBLE SIDEBAR -->
  <aside class="w-64 bg-[#0c0f14] border-r border-[#1e2838] flex flex-col justify-between shrink-0 transition-all duration-300">
    <div>
      <div class="h-16 px-6 flex items-center gap-3 border-b border-[#1e2838]">
        <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center font-black text-white shadow shadow-emerald-500/10">A</div>
        <span class="text-lg font-bold tracking-tight text-white sidebar-label">ATOM CHAT</span>
      </div>

      <!-- Chats history panel -->
      <div class="p-4 space-y-4">
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-bold text-gray-500 tracking-wider uppercase">Conversations</span>
          <button onclick="createNewChat()" class="text-xs font-bold text-emerald-400 hover:text-emerald-300">+ New</button>
        </div>
        <div id="conversationsList" class="space-y-1 overflow-y-auto max-h-[400px]">
          <div class="text-center py-6 text-gray-500 text-xs">No active chats.</div>
        </div>
      </div>
    </div>
    <div class="p-4 border-t border-[#1e2838]">
      <a href="/admin" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold bg-[#11151c] text-emerald-400 hover:bg-[#16202e] border border-[#1e2838] transition-all">
        Control Panel &rarr;
      </a>
    </div>
  </aside>

  <!-- MAIN CHAT PANEL -->
  <div class="flex-1 flex flex-col overflow-hidden bg-[#080a0d]">
    <!-- Header -->
    <header class="h-16 border-b border-[#1e2838] bg-[#0c0f14]/80 backdrop-blur px-8 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
        <span class="font-bold text-white text-sm" id="chatTitle">Active Chat Session</span>
      </div>
      <div class="flex items-center gap-3">
        <select id="chatModel" class="h-9 px-3 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs text-white focus:outline-none focus:border-emerald-500/50 font-mono">
          <option value="openai/gpt-oss-120b">openai/gpt-oss-120b</option>
          <option value="gemini-3.6-flash">gemini-3.6-flash</option>
          <option value="gpt-4o-mini">gpt-4o-mini</option>
          <option value="llama3.1">llama3.1 (Local)</option>
        </select>
      </div>
    </header>

    <!-- Message lists -->
    <div id="chatMessages" class="flex-1 overflow-y-auto p-8 space-y-6">
      <div class="text-center py-12 text-gray-500 text-xs">Select or create a conversation from the sidebar to begin reasoning.</div>
    </div>

    <!-- User inputs -->
    <div class="p-6 border-t border-[#1e2838] bg-[#0c0f14]/30">
      <form id="chatForm" class="flex gap-4" onsubmit="sendMessage(event)">
        <input type="text" id="userInput" placeholder="Ask ATOM a technical coding question..." class="flex-1 h-12 px-4 rounded-xl bg-[#080a0d] border border-[#1e2838] text-xs text-white focus:outline-none focus:border-emerald-500/50" disabled>
        <button type="submit" id="sendBtn" class="px-6 py-3 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-white shadow shadow-emerald-500/10 transition-all" disabled>
          Send
        </button>
      </form>
    </div>
  </div>

  <script src="/admin/js/shared.js"></script>
  <script>
    let activeChatId = null;

    async function loadChats() {
      const list = document.getElementById('conversationsList');
      try {
        const json = await apiFetch('/chats');

        if (json.success && json.data && json.data.length > 0) {
          list.innerHTML = json.data.map(c => `
            <button onclick="selectChat(${c.id}, '${escapeHtml(c.title)}')" class="w-full text-left px-3 py-2 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#1e2735] hover:text-white truncate block ${c.id === activeChatId ? 'bg-[#1e2735] text-white' : ''}">
              ${escapeHtml(c.title || 'Conversation #' + c.id)}
            </button>
          `).join('');
        } else {
          list.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs">No active chats.</div>';
        }
      } catch (e) {
        list.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs">No active chats.</div>';
      }
    }

    async function createNewChat() {
      const title = prompt('Enter conversation topic:');
      if (!title) return;

      try {
        const json = await apiFetch('/chats', {
          method: 'POST',
          body: JSON.stringify({ title, model: document.getElementById('chatModel').value, provider: 'Groq' })
        });
        if (json.success && json.data && json.data.id) {
          activeChatId = json.data.id;
          selectChat(activeChatId, title);
        }
      } catch (e) {}
    }

    async function selectChat(id, title) {
      activeChatId = id;
      document.getElementById('chatTitle').textContent = title;
      document.getElementById('userInput').disabled = false;
      document.getElementById('sendBtn').disabled = false;
      loadChats();

      const messagesBox = document.getElementById('chatMessages');
      messagesBox.innerHTML = '<div class="text-center py-6 text-gray-500 text-xs">Retrieving messages...</div>';

      try {
        const json = await apiFetch('/chats/' + id + '/messages');

        if (json.success && json.data && json.data.length > 0) {
          messagesBox.innerHTML = json.data.map(m => {
            const isUser = m.role === 'user';
            const bg = isUser ? 'bg-[#1a2332]' : 'bg-[#11151c]';
            const border = isUser ? 'border-blue-500/10' : 'border-[#1e2838]';
            
            return `
              <div class="flex flex-col space-y-2 max-w-3xl ${isUser ? 'ml-auto' : 'mr-auto'}">
                <span class="text-[10px] font-bold text-gray-500">${isUser ? 'YOU' : 'ATOM'}</span>
                <div class="p-4 rounded-2xl border ${border} ${bg} text-xs text-gray-300 leading-relaxed font-mono whitespace-pre-wrap">${escapeHtml(m.content)}</div>
              </div>
            `;
          }).join('');
          messagesBox.scrollTop = messagesBox.scrollHeight;
        } else {
          messagesBox.innerHTML = '<div class="text-center py-12 text-gray-500 text-xs">Ask ATOM anything to start the conversation!</div>';
        }
      } catch (e) {}
    }

    async function sendMessage(event) {
      event.preventDefault();
      const input = document.getElementById('userInput');
      const text = input.value.trim();
      if (!text || !activeChatId) return;

      input.value = '';
      input.disabled = true;

      const messagesBox = document.getElementById('chatMessages');
      // Append user message immediately
      messagesBox.innerHTML += `
        <div class="flex flex-col space-y-2 max-w-3xl ml-auto">
          <span class="text-[10px] font-bold text-gray-500">YOU</span>
          <div class="p-4 rounded-2xl border border-blue-500/10 bg-[#1a2332] text-xs text-gray-300 leading-relaxed font-mono whitespace-pre-wrap">${escapeHtml(text)}</div>
        </div>
      `;
      messagesBox.scrollTop = messagesBox.scrollHeight;

      try {
        const json = await apiFetch('/chats/' + activeChatId + '/messages', {
          method: 'POST',
          body: JSON.stringify({ message: text }) // Hits AiChat send trigger automatically
        });
        if (json.success) {
          selectChat(activeChatId, document.getElementById('chatTitle').textContent);
        }
      } catch (e) {
        showToast('Failed to send message', 'error');
      } finally {
        input.disabled = false;
        input.focus();
      }
    }

    loadChats();
  </script>
</body>
</html>
