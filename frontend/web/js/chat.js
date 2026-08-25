var API_BASE = 'http://localhost:8080/api';
var API_V1 = 'http://localhost:8080/api/v1';
var authToken = localStorage.getItem('atom_web_token');
var activeChatId = parseInt(localStorage.getItem('atom_web_chat') || '0', 10);
var currentUserId = null;
var msgCount = 0;
var selectedModel = localStorage.getItem('atom_web_model') || 'openai/gpt-oss-120b';
var selectedProvider = 'Groq';

function apiUrl(path) { return API_BASE + path; }
function apiV1Url(path) { return API_V1 + path; }

function apiHeaders() {
  var h = { 'Content-Type': 'application/json' };
  if (authToken) h['Authorization'] = 'Bearer ' + authToken;
  return h;
}

function apiOpts(method, body) {
  var opts = { method: method, headers: apiHeaders() };
  if (body) opts.body = JSON.stringify(body);
  return opts;
}

async function safeParseResponse(resp) {
  var text = await resp.text();
  if (!text || !text.trim()) {
    return { success: resp.ok, message: resp.ok ? '' : 'Empty response from server' };
  }
  var trimmed = text.trim();
  if (trimmed.startsWith('<') || trimmed.toLowerCase().startsWith('<!doctype')) {
    var titleMatch = trimmed.match(/<title>([^<]+)<\/title>/i);
    return { success: false, message: titleMatch ? titleMatch[1] : 'Server returned HTML page (' + resp.status + ')' };
  }
  try {
    return JSON.parse(trimmed);
  } catch (e) {
    return { success: false, message: 'Invalid JSON: ' + e.message };
  }
}

async function apiFetch(path, method, body) {
  try {
    var resp = await fetch(apiUrl(path), apiOpts(method, body));
    return await safeParseResponse(resp);
  } catch (e) {
    return { success: false, message: 'Connection failed: ' + e.message };
  }
}

async function apiV1Fetch(path, method, body) {
  try {
    var resp = await fetch(apiV1Url(path), apiOpts(method, body));
    return await safeParseResponse(resp);
  } catch (e) {
    return { success: false, message: 'Connection failed: ' + e.message };
  }
}

function showToast(msg) {
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show';
  setTimeout(function() { t.className = 'toast'; }, 3000);
}

function updateStatus(connected) {
  var dot = document.getElementById('statusDot');
  var text = document.getElementById('statusText');
  if (connected) {
    dot.className = 'status-dot';
    text.textContent = 'Connected';
    text.style.color = 'var(--green)';
  } else {
    dot.className = 'status-dot offline';
    text.textContent = 'Offline';
    text.style.color = 'var(--red)';
  }
}

function escHtml(v) {
  return v.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function timeStr() {
  var now = new Date();
  var h = now.getHours(); var m = now.getMinutes();
  return (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
}

function addMsg(type, content) {
  msgCount++;
  var container = document.getElementById('messages');
  var wel = document.getElementById('welcome');
  if (wel) wel.style.display = 'none';

  var d = document.createElement('div');
  d.className = 'msg ' + type;
  d.innerHTML = '<div class="msg-role">' + (type === 'user' ? 'You' : 'Atom') + '</div>'
    + '<div class="msg-content">' + escHtml(content) + '</div>'
    + '<div class="msg-time">' + timeStr() + '</div>';
  container.appendChild(d);
  container.scrollTop = container.scrollHeight;
}

function addMsgRaw(type, contentHtml) {
  msgCount++;
  var container = document.getElementById('messages');
  var wel = document.getElementById('welcome');
  if (wel) wel.style.display = 'none';

  var d = document.createElement('div');
  d.className = 'msg ' + type;
  d.innerHTML = '<div class="msg-role">' + (type === 'user' ? 'You' : 'Atom') + '</div>'
    + '<div class="msg-content">' + contentHtml + '</div>'
    + '<div class="msg-time">' + timeStr() + '</div>';
  container.appendChild(d);
  container.scrollTop = container.scrollHeight;
}

function showTyping() {
  document.getElementById('typingIndicator').style.display = 'flex';
}

function hideTyping() {
  document.getElementById('typingIndicator').style.display = 'none';
}

async function ensureAuth() {
  var email = localStorage.getItem('atom_web_email');
  var token = localStorage.getItem('atom_web_token');

  if (token && email) {
    authToken = token;
    var me = await apiFetch('/auth/me', 'GET');
    if (me.success) {
      currentUserId = me.data.id;
      return true;
    }
    localStorage.removeItem('atom_web_token');
    localStorage.removeItem('atom_web_email');
    authToken = null;
  }

  email = 'web_' + Date.now() + '@atom.local';
  var pass = 'atom_' + Math.random().toString(36).slice(2, 10);

  var result = await apiFetch('/auth/register', 'POST', { email: email, password: pass, name: 'Web User' });
  if (result.success) {
    authToken = result.data.token;
    currentUserId = result.data.user.id;
    localStorage.setItem('atom_web_token', authToken);
    localStorage.setItem('atom_web_email', email);
    return true;
  }

  return false;
}

async function checkServer() {
  try {
    var resp = await fetch(API_BASE.replace('/api', ''), { method: 'GET' });
    var json = await resp.json();
    return json.success === true;
  } catch (e) {
    return false;
  }
}

async function sendMsg() {
  var input = document.getElementById('msgInput');
  var text = input.value.trim();
  if (!text) return;

  if (!authToken) {
    var authed = await ensureAuth();
    if (!authed) {
      showToast('Cannot connect to backend. Make sure the API is running.');
      return;
    }
  }

  addMsg('user', text);
  input.value = '';
  input.style.height = '44px';

  if (!activeChatId) {
    await createNewChat();
  }

  showTyping();

  try {
    var result = await apiFetch('/chat/' + activeChatId + '/preview', 'POST', { message: text });
    hideTyping();

    if (result.success) {
      addMsg('assistant', result.data.content);
    } else {
      addMsgRaw('assistant', '<span style="color:var(--red)">Error: ' + escHtml(result.message || 'Unknown error') + '</span>');
    }
  } catch (e) {
    hideTyping();
    addMsgRaw('assistant', '<span style="color:var(--red)">Connection error: ' + escHtml(e.message) + '</span>');
  }
}

function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMsg();
  }
}

async function createNewChat() {
  var result = await apiFetch('/chats', 'POST', {
    title: 'New Chat',
    model: selectedModel,
    provider: selectedProvider
  });

  if (result.success) {
    activeChatId = result.data.id;
    localStorage.setItem('atom_web_chat', activeChatId);
    document.getElementById('chatTitle').textContent = 'New Chat';
    loadChatList();
  }
}

async function loadChatList() {
  var result = await apiFetch('/chats?per_page=50', 'GET');
  var list = document.getElementById('chatList');
  list.innerHTML = '';

  if (result.success && result.data) {
    var chats = Array.isArray(result.data) ? result.data : [];
    chats.forEach(function(chat) {
      var item = document.createElement('div');
      item.className = 'chat-list-item' + (chat.id === activeChatId ? ' active' : '');
      item.innerHTML = '<span class="cli-title">' + escHtml(chat.title || 'Untitled') + '</span>'
        + '<span class="cli-count">' + (chat.message_count || '0') + '</span>';
      item.onclick = function() { loadChat(chat.id, chat.title); };
      list.appendChild(item);
    });
  }
}

async function loadChat(chatId, title) {
  activeChatId = chatId;
  localStorage.setItem('atom_web_chat', chatId);
  document.getElementById('chatTitle').textContent = title || 'Chat';

  var result = await apiFetch('/chats/' + chatId, 'GET');
  var container = document.getElementById('messages');
  container.innerHTML = '';

  if (result.success && result.data && result.data.messages) {
    result.data.messages.forEach(function(msg) {
      addMsg(msg.role === 'user' ? 'user' : 'assistant', msg.content);
    });
  }

  loadChatList();
}

function newChat() {
  activeChatId = 0;
  localStorage.removeItem('atom_web_chat');
  document.getElementById('chatTitle').textContent = 'General Chat';
  document.getElementById('messages').innerHTML = ''
    + '<div class="welcome" id="welcome">'
    + '<div class="welcome-icon">A</div>'
    + '<h2>Welcome to Atom</h2>'
    + '<p>Your AI assistant with backend persistence. Type a message to get started.</p>'
    + '</div>';
}

function clearChat() {
  newChat();
  showToast('Chat cleared');
}

// ===== Page Switcher JavaScript Functions =====

function switchPage(pageId) {
  var sections = document.querySelectorAll('.page-section');
  sections.forEach(function(sec) {
    sec.classList.remove('active');
  });

  var navItems = document.querySelectorAll('.sidebar-nav .nav-item');
  navItems.forEach(function(item) {
    item.classList.remove('active');
  });

  var activeSec = document.getElementById('page-' + pageId);
  if (activeSec) activeSec.classList.add('active');

  // Load page specific data
  if (pageId === 'dashboard') loadDashboard();
  if (pageId === 'memory') loadMemories();
  if (pageId === 'knowledge') loadKnowledge();
  if (pageId === 'learning') loadLearning();
  if (pageId === 'workspace') loadWorkspace();
}

function toggleSidebar() {
  var sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('sidebar-collapsed');
}

// ===== Load Dashboard Stats =====
async function loadDashboard() {
  var res = await apiV1Fetch('/system/status', 'GET');
  if (res.success && res.data) {
    document.getElementById('dashProvider').textContent = res.data.provider_name || 'GEMINI';
  }
}

// ===== Stored Memories =====
async function loadMemories() {
  var res = await apiV1Fetch('/memory', 'GET');
  var grid = document.getElementById('memoriesGrid');
  grid.innerHTML = '';
  if (res.success && res.data) {
    res.data.forEach(function(mem) {
      var card = document.createElement('div');
      card.className = 'card';
      card.innerHTML = '<div class="card-title">' + escHtml(mem.title || 'MEMORY') + '</div>'
        + '<div class="card-value" style="font-size:16px;">' + escHtml(mem.content || mem.note || '') + '</div>';
      grid.appendChild(card);
    });
  }
}

// ===== Knowledge PDFs =====
async function loadKnowledge() {
  var res = await apiV1Fetch('/knowledge', 'GET');
  var grid = document.getElementById('pdfLibraryGrid');
  grid.innerHTML = '';
  if (res.success && res.data) {
    res.data.forEach(function(doc) {
      var card = document.createElement('div');
      card.className = 'card';
      card.innerHTML = '<div class="card-title">PDF DOCUMENT</div>'
        + '<div class="card-value" style="font-size:16px;">' + escHtml(doc.title || 'Document') + '</div>'
        + '<div style="font-size:11px;color:var(--text-muted);margin-top:8px;">' + (doc.chunk_count || '0') + ' Chunks Indexed</div>';
      grid.appendChild(card);
    });
  }
}

// ===== PDF Upload Implementation =====
async function uploadPdf() {
  var input = document.getElementById('pdfUploadInput');
  var file = input.files[0];
  if (!file) return;

  var formData = new FormData();
  formData.append('file', file);

  showToast('Uploading PDF...');
  try {
    var resp = await fetch(apiV1Url('/knowledge/upload'), {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + authToken },
      body: formData
    });
    var json = await resp.json();
    if (json.success) {
      showToast('PDF ingested and indexed successfully!');
      loadKnowledge();
    } else {
      showToast('Upload failed: ' + json.message);
    }
  } catch (e) {
    showToast('Upload error: ' + e.message);
  }
}

// ===== Learning levels =====
async function loadLearning() {
  var res = await apiV1Fetch('/learning', 'GET');
  var grid = document.getElementById('learningTopicsGrid');
  grid.innerHTML = '';
  if (res.success && res.data && res.data.topics) {
    res.data.topics.forEach(function(t) {
      var barCount = Math.round(t.score / 5);
      var bar = '█'.repeat(barCount) + '░'.repeat(20 - barCount);
      var card = document.createElement('div');
      card.className = 'card';
      card.innerHTML = '<div class="card-title">' + escHtml(t.topic) + '</div>'
        + '<div class="card-value" style="font-size:18px;">' + t.score + '% ' + t.level + '</div>'
        + '<div style="font-family:monospace;color:var(--green);font-size:11px;margin-top:8px;">' + bar + '</div>';
      grid.appendChild(card);
    });
  }
}

// ===== Workspace Explorer =====
async function loadWorkspace() {
  var res = await apiV1Fetch('/workspace', 'GET');
  var list = document.getElementById('workspaceFilesList');
  list.innerHTML = '';
  if (res.success && res.data && res.data.files) {
    res.data.files.forEach(function(file) {
      var row = document.createElement('div');
      row.className = 'code-list-item';
      row.innerHTML = '<span>📄 ' + escHtml(file.name) + '</span>'
        + '<span style="color:var(--text-muted);">' + file.size + ' bytes</span>';
      list.appendChild(row);
    });
  }
}

function onModelChange() {
  var sel = document.getElementById('modelSelect');
  var parts = (sel.value || '').split(':');
  selectedModel = parts[0] || sel.value;
  var val = sel.value;
  if (val.indexOf('gemini') === 0) selectedProvider = 'Gemini';
  else if (val.indexOf('openai/') === 0) selectedProvider = 'Groq';
  else if (val.indexOf('gpt-') === 0) selectedProvider = 'OpenAI';
  else if (val.indexOf('llama-') === 0) selectedProvider = 'Ollama';
  else selectedProvider = 'Ollama';
  localStorage.setItem('atom_web_model', selectedModel);
  if (activeChatId) {
    apiFetch('/chats/' + activeChatId, 'PUT', { model: selectedModel, provider: selectedProvider });
  }
}

window.addEventListener('load', async function() {
  var serverAlive = await checkServer();
  if (!serverAlive) {
    updateStatus(false);
    return;
  }

  var sel = document.getElementById('modelSelect');
  if (sel) sel.value = selectedModel;

  var connected = await ensureAuth();
  updateStatus(connected);

  if (connected) {
    loadChatList();
    loadDashboard();
  }
});

// Settings tabs
function switchTab(tabId) {
  var contents = document.querySelectorAll('.tab-content');
  contents.forEach(function(c) { c.classList.remove('active'); });
  var tabs = document.querySelectorAll('.settings-tab');
  tabs.forEach(function(t) { t.classList.remove('active'); });

  document.getElementById('tab-' + tabId).classList.add('active');
  document.getElementById('tabbtn-' + tabId).classList.add('active');
}

function openSettingsModal() {
  document.getElementById('settingsModal').classList.add('show');
}
function closeSettingsModal() {
  document.getElementById('settingsModal').classList.remove('show');
}
