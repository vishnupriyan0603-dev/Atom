// Shared JavaScript logic for ATOM Admin Control panel: auth guard, API helper,
// collapsibility, global search overlays, toasts, and event handlers.

var ATOM_API = 'http://localhost:8080/api';
var ATOM_TOKEN_KEY = 'atom_web_token';
var ATOM_EMAIL_KEY = 'atom_web_email';

// ===== Auth helpers =====
function getAuthToken() {
  return localStorage.getItem(ATOM_TOKEN_KEY) || '';
}
function setAuthToken(token, email) {
  if (token) localStorage.setItem(ATOM_TOKEN_KEY, token);
  if (email) localStorage.setItem(ATOM_EMAIL_KEY, email);
}
function clearAuthToken() {
  localStorage.removeItem(ATOM_TOKEN_KEY);
  localStorage.removeItem(ATOM_EMAIL_KEY);
}

function authHeaders(json) {
  var h = {};
  if (json) h['Content-Type'] = 'application/json';
  var token = getAuthToken();
  if (token) h['Authorization'] = 'Bearer ' + token;
  return h;
}

/**
 * Authenticated fetch wrapper. Attaches the JWT and redirects to the admin
 * login page when the token is missing, invalid, or expired.
 */
async function apiFetch(path, options) {
  options = options || {};
  options.headers = Object.assign(authHeaders(options.method !== 'GET'), options.headers || {});
  if (options.body && !options.headers['Content-Type']) {
    options.headers['Content-Type'] = 'application/json';
  }

  var resp;
  try {
    resp = await fetch(ATOM_API + path, options);
  } catch (e) {
    return { success: false, message: 'Connection failed: ' + e.message };
  }

  if (resp.status === 401) {
    handleAuthFailure();
    return { success: false, message: 'Session expired. Please log in again.', status: 401 };
  }

  try {
    var json = await resp.json();
    if (!json.request_id) json.request_id = 'N/A';
    return json;
  } catch (e) {
    return { success: false, message: 'Invalid response from server', status: resp.status };
  }
}

function getAdminLoginUrl(next) {
  var p = window.location.pathname;
  var pos = p.indexOf('/frontend/web');
  var base = (pos !== -1) ? p.substring(0, pos + 13) : '';
  var target = (base ? base + '/admin/login.php' : '/admin/login.php');
  if (next) {
    target += '?next=' + encodeURIComponent(next);
  }
  return target;
}

function handleAuthFailure() {
  clearAuthToken();
  var next = window.location.pathname;
  if (next.indexOf('/login.php') !== -1) return;
  window.location.href = getAdminLoginUrl(next);
}

/**
 * Ensures the current visitor holds a valid admin session. Redirects to the
 * login page when unauthenticated. Safe to call on any protected admin page.
 */
async function requireAuth() {
  var token = getAuthToken();
  if (!token) {
    handleAuthFailure();
    return false;
  }

  var json = await apiFetch('/auth/me', { method: 'GET' });
  if (json.success && json.data) {
    return true;
  }
  handleAuthFailure();
  return false;
}

function logoutAdmin() {
  apiFetch('/auth/logout', { method: 'POST' });
  clearAuthToken();
  window.location.href = getAdminLoginUrl();
}

// ===== XSS-safe rendering helper =====
function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// ===== Sidebar =====
function toggleSidebar() {
  const sidebar = document.getElementById('adminSidebar');
  const labels = document.querySelectorAll('.sidebar-label');
  const icon = document.getElementById('collapseIcon');

  if (sidebar.classList.contains('w-64')) {
    sidebar.classList.remove('w-64');
    sidebar.classList.add('w-20');
    labels.forEach(l => l.style.display = 'none');
    if (icon) icon.innerHTML = '&rarr;';
  } else {
    sidebar.classList.remove('w-20');
    sidebar.classList.add('w-64');
    labels.forEach(l => l.style.display = 'inline');
    if (icon) icon.innerHTML = '&larr;';
  }
}

function toggleSidebarMobile() {
  const sidebar = document.getElementById('adminSidebar');
  sidebar.classList.toggle('-translate-x-full');
}

// Global Keyboard Shortcut mapping (Ctrl + K) to open global search panel
document.addEventListener('keydown', function(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    openSearchOverlay();
  }
});

function openSearchOverlay() {
  const overlay = document.getElementById('searchOverlay');
  if (!overlay) return;
  overlay.classList.remove('hidden');
  const input = document.getElementById('overlaySearchInput');
  if (input) input.focus();
}

function closeSearchOverlay() {
  const overlay = document.getElementById('searchOverlay');
  if (overlay) overlay.classList.add('hidden');
}

// Simple debounce helper
function debounce(func, wait) {
  let timeout;
  return function(...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
}

const handleSearch = debounce(async function(query) {
  const resultsEl = document.getElementById('searchResults');
  query = (query || '').trim();
  if (query.length < 2) {
    resultsEl.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">Type at least 2 characters to start querying...</div>';
    return;
  }

  resultsEl.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">Querying ATOM database...</div>';

  const json = await apiFetch('/analytics/global-search?query=' + encodeURIComponent(query), { method: 'GET' });

  if (json.success && json.data && json.data.length > 0) {
    resultsEl.innerHTML = json.data.map(item => `
      <div class="p-3 rounded-xl bg-[#080a0d] border border-[#1e2838] hover:border-emerald-500/20 transition-all space-y-1">
        <div class="flex items-center justify-between text-[10px]">
          <span class="px-2 py-0.5 rounded font-bold uppercase bg-emerald-500/10 text-emerald-400">${escapeHtml(item.type)}</span>
          <span class="text-gray-500">${escapeHtml(item.source || 'General')}</span>
        </div>
        <h4 class="text-white font-bold text-xs">${escapeHtml(item.title)}</h4>
        <p class="text-[11px] text-gray-400 truncate">${escapeHtml(item.content || '')}</p>
      </div>
    `).join('');
  } else {
    resultsEl.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">No records found matching "' + escapeHtml(query) + '"</div>';
  }
}, 300);

const overlayInput = document.getElementById('overlaySearchInput');
if (overlayInput) {
  overlayInput.addEventListener('input', function(e) {
    handleSearch(e.target.value);
  });
}

// Close overlay on escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeSearchOverlay();
  }
});

// ===== Advanced Toast Notifications =====
// Reusable toaster for the ATOM admin panel. Types: success, error, warning, info.
function showToast(message, type) {
  type = type || 'info';

  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:360px;';
    document.body.appendChild(container);
  }

  const styles = {
    success: { icon: '&#10003;', accent: '#10b981', bg: 'rgba(16,185,129,0.12)', border: 'rgba(16,185,129,0.45)', label: 'SUCCESS' },
    error:   { icon: '&#10005;', accent: '#ef4444', bg: 'rgba(239,68,68,0.12)', border: 'rgba(239,68,68,0.45)', label: 'ERROR' },
    warning: { icon: '&#9888;', accent: '#f59e0b', bg: 'rgba(245,158,11,0.12)', border: 'rgba(245,158,11,0.45)', label: 'WARNING' },
    info:    { icon: '&#8505;', accent: '#3b82f6', bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.45)', label: 'INFO' }
  };
  const s = styles[type] || styles.info;

  const toast = document.createElement('div');
  toast.style.cssText = 'display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-radius:12px;'
    + 'background:' + s.bg + ';border:1px solid ' + s.border + ';backdrop-filter:blur(8px);'
    + 'box-shadow:0 12px 40px rgba(0,0,0,0.55);opacity:0;transform:translateX(30px);'
    + 'transition:all 0.3s cubic-bezier(0.16,1,0.3,1);';
  toast.innerHTML =
    '<div style="flex:0 0 28px;width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;'
    + 'font-size:13px;font-weight:900;color:#fff;background:' + s.accent + ';">' + s.icon + '</div>'
    + '<div style="flex:1;min-width:0;">'
    + '<div style="font-size:10px;font-weight:800;letter-spacing:0.06em;color:' + s.accent + ';">' + s.label + '</div>'
    + '<div style="font-size:13px;font-weight:500;color:#f0f4f8;margin-top:2px;line-height:1.4;">'
    + escapeHtml(message) + '</div></div>'
    + '<button aria-label="Dismiss" style="flex:0 0 auto;background:none;border:none;color:#8b93a1;cursor:pointer;font-size:15px;line-height:1;padding:0;">&times;</button>';

  container.appendChild(toast);

  // Entry animation
  requestAnimationFrame(() => { toast.style.opacity = '1'; toast.style.transform = 'translateX(0)'; });

  // Dismiss handlers
  const dismiss = () => {
    toast.style.opacity = '0'; toast.style.transform = 'translateX(30px)';
    setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
  };
  toast.querySelector('button').addEventListener('click', dismiss);
  setTimeout(dismiss, 3500);

  return toast;
}

// ===== Admin auth gate =====
// Automatically protect admin pages: only runs inside /admin/ views excluding login.php
(async function initAdminAuth() {
  var p = window.location.pathname;
  if (p.indexOf('/login.php') !== -1) return;
  // Only guard pages inside admin directory
  if (p.indexOf('/admin/') === -1 && p.indexOf('/admin.php') === -1) return;
  // If token is present, verify; otherwise allow local development access
  var token = getAuthToken();
  if (token) {
    await requireAuth();
  }
})();
