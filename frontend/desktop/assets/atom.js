var msgCount = 0;
var sessionStart = Date.now();
var statusText = document.getElementById('statusText');
var statusBadge = document.getElementById('statusBadge');
var API_BASE = 'http://localhost:8080/api';
var authToken = localStorage.getItem('atom_token');
var activeChatId = parseInt(localStorage.getItem('atom_active_chat') || '0', 10);
var currentUserId = null;

function apiUrl(path) { return API_BASE + path; }

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

async function apiFetch(path, method, body) {
    try {
        var resp = await fetch(apiUrl(path), apiOpts(method, body));
        var json = await resp.json();
        return json;
    } catch (e) {
        return { success: false, message: 'Connection failed: ' + e.message };
    }
}

async function ensureAuth() {
    var email = localStorage.getItem('atom_email');
    var token = localStorage.getItem('atom_token');

    if (token && email) {
        authToken = token;
        var me = await apiFetch('/auth/me', 'GET');
        if (me.success) {
            currentUserId = me.data.id;
            return true;
        }
    }

    email = 'user_' + Date.now() + '@atom.local';
    var pass = 'atom_' + Math.random().toString(36).slice(2, 10);

    var result = await apiFetch('/auth/register', 'POST', { email: email, password: pass, name: 'Atom User' });
    if (result.success) {
        authToken = result.data.token;
        currentUserId = result.data.user.id;
        localStorage.setItem('atom_token', authToken);
        localStorage.setItem('atom_email', email);
        return true;
    }

    result = await apiFetch('/auth/login', 'POST', { email: email, password: pass });
    if (result.success) {
        authToken = result.data.token;
        currentUserId = result.data.user.id;
        localStorage.setItem('atom_token', authToken);
        localStorage.setItem('atom_email', email);
        return true;
    }

    return false;
}

async function sendMsg() {
    var input = document.getElementById('msgInput');
    var box = document.getElementById('msgBox');
    var text = input.value.replace(/^\s+|\s+$/g, '');
    if (!text) return;

    var wel = box.querySelector('.welcome');
    if (wel) wel.style.display = 'none';

    if (!authToken) {
        await ensureAuth();
    }

    msgCount++;
    document.getElementById('msgCount').textContent = msgCount;
    addMsg(box, 'self', text);

    if (!activeChatId) {
        await createNewChat();
    }

    input.value = '';
    box.scrollTop = box.scrollHeight;

    var typing = document.getElementById('typingIndicator');
    typing.style.display = 'flex';
    box.scrollTop = box.scrollHeight;

    try {
        var result = await apiFetch('/chat/' + activeChatId + '/preview', 'POST', { message: text });

        typing.style.display = 'none';

        if (result.success) {
            addMsg(box, 'bot', result.data.content);
        } else {
            addMsg(box, 'bot', 'Error: ' + (result.message || 'Failed to get response'));
        }
    } catch (e) {
        typing.style.display = 'none';
        addMsg(box, 'bot', 'Connection error. Make sure the backend is running at ' + API_BASE);
    }

    box.scrollTop = box.scrollHeight;
}

async function createNewChat() {
    var result = await apiFetch('/chats', 'POST', {
        title: 'New Chat',
        model: 'llama3.1',
        provider: 'Ollama'
    });

    if (result.success) {
        activeChatId = result.data.id;
        localStorage.setItem('atom_active_chat', activeChatId);
    }
}

async function loadChats() {
    var result = await apiFetch('/chats?per_page=100', 'GET');
    if (result.success && result.data) {
        return result.data;
    }
    return [];
}

function addMsg(container, type, text) {
    var d = document.createElement('div');
    d.className = 'msg ' + type;
    var label = type === 'self' ? 'You' : 'Atom';
    var now = new Date();
    var h = now.getHours(); var m = now.getMinutes();
    var time = (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
    d.innerHTML = '<div class="ml">' + label + '</div><div class="mt">' + escHtml(text) + '</div><div class="mtime">' + time + '</div>';
    container.appendChild(d);
}

function escHtml(v) {
    return v.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function clearChat() {
    var box = document.getElementById('msgBox');
    msgCount = 0;
    document.getElementById('msgCount').textContent = '0';
    activeChatId = 0;
    localStorage.removeItem('atom_active_chat');
    box.innerHTML = '<div class="welcome">Chat cleared. Type a message to start again.</div>';
    showToast('Chat history cleared');
}

function quickAct(action) {
    var box = document.getElementById('msgBox');
    var wel = box.querySelector('.welcome');
    if (wel) wel.style.display = 'none';
    if (action === 'help') {
        addMsg(box, 'bot', 'Available commands:\n  help   - Show this help\n  status - Check system status\n  clear  - Clear chat\n  models - List AI models\n\nType any message to chat with Atom.');
    } else if (action === 'status') {
        var uptime = Math.floor((Date.now() - sessionStart) / 60000);
        var conn = authToken ? 'Connected to backend' : 'Not connected';
        addMsg(box, 'bot', 'Atom System Status\n  Status: Online\n  Version: 1.0\n  Platform: Windows HTA\n  Theme: Dark\n  Messages: ' + msgCount + '\n  Uptime: ' + uptime + 'm\n  Backend: ' + conn + '\n  API: ' + API_BASE);
    } else if (action === 'clear') {
        clearChat();
    }
    box.scrollTop = box.scrollHeight;
}

function getResp(text) {
    var l = text.toLowerCase();
    if (l.indexOf('hello') !== -1 || l.indexOf('hi') !== -1 || l.indexOf('hey') !== -1) return 'Hello! How can I assist you today?';
    if (l.indexOf('help') !== -1) return 'I am here to help. Try asking about my features, my status, or just say hello.';
    if (l.indexOf('who') !== -1 || l.indexOf('what are you') !== -1) return 'I am Atom, your personal AI assistant.';
    if (l.indexOf('status') !== -1 || l.indexOf('online') !== -1) return 'All systems operational.';
    if (l.indexOf('thank') !== -1) return 'You are welcome!';
    if (l.indexOf('bye') !== -1 || l.indexOf('goodbye') !== -1) return 'Goodbye!';
    if (l.indexOf('theme') !== -1 || l.indexOf('dark') !== -1) return 'Atom runs in Dark theme.';
    return "I'll connect to the backend for a proper response. Please wait...";
}

function showToast(msg) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show';
    setTimeout(function() { t.className = 'toast'; }, 2500);
}

function closestEl(el, selector) {
    while (el) {
        if (el.tagName) {
            var fn = el.matches || el.msMatchesSelector || el.webkitMatchesSelector;
            if (fn && fn.call(el, selector)) return el;
        }
        el = el.parentNode;
    }
    return null;
}

document.addEventListener('click', function(e) {
    var item = closestEl(e.target, '.nav-item');
    if (!item) return;
    var all = document.querySelectorAll('.nav-item');
    for (var i = 0; i < all.length; i++) { all[i].className = 'nav-item'; }
    item.className = 'nav-item active';
});

setInterval(function() {
    var m = Math.floor((Date.now() - sessionStart) / 60000);
    document.getElementById('uptime').textContent = m + 'm';
}, 10000);

window.addEventListener('load', async function() {
    var connected = await ensureAuth();
    if (connected) {
        statusText.textContent = 'Backend Connected';
        statusBadge.innerHTML = '&#9679; Backend Online';
        showToast('Connected to Atom backend API');
    } else {
        statusText.textContent = 'Backend Offline';
        statusBadge.innerHTML = '&#9679; Local Mode';
        showToast('Backend not available - using local responses');
    }
});
