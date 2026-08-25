var ATOM_API = getApiBaseUrl();
var ATOM_TOKEN_KEY = 'atom_web_token';
var ATOM_EMAIL_KEY = 'atom_web_email';

function getApiBaseUrl() {
  if (typeof window !== 'undefined' && window.ATOM_API_BASE) {
    return window.ATOM_API_BASE;
  }
  if (typeof window !== 'undefined' && window.location) {
    var loc = window.location;
    if (loc.port === '8080') {
      return loc.origin + '/api';
    }
    var p = loc.pathname;
    var atomIdx = p.toLowerCase().indexOf('/atom');
    if (atomIdx !== -1) {
      var basePath = p.substring(0, atomIdx + 5);
      return loc.origin + basePath + '/backend/public/index.php/api';
    }
  }
  return 'http://localhost:8080/api';
}

// ===== Safe JSON & HTTP Fetcher (Prevents Unexpected token '<' <!DOCTYPE errors) =====
async function safeJsonFetch(url, options) {
  try {
    var resp = await fetch(url, options);
    var text = await resp.text();

    if (!text || !text.trim()) {
      return {
        success: resp.ok,
        status: resp.status,
        data: null,
        error: resp.ok ? null : ('Server returned empty response (' + resp.status + ')')
      };
    }

    var trimmed = text.trim();
    // Intercept HTML error documents (e.g. <!DOCTYPE html> or <html> 404/500 pages)
    if (trimmed.startsWith('<') || trimmed.toLowerCase().startsWith('<!doctype')) {
      var titleMatch = trimmed.match(/<title>([^<]+)<\/title>/i);
      var htmlMessage = titleMatch ? titleMatch[1].trim() : ('HTTP ' + resp.status + ' ' + resp.statusText);
      return {
        success: false,
        status: resp.status,
        data: null,
        error: htmlMessage,
        is_html: true,
        raw_html: trimmed.substring(0, 300)
      };
    }

    var json;
    try {
      json = JSON.parse(trimmed);
    } catch (parseErr) {
      return {
        success: false,
        status: resp.status,
        data: null,
        error: 'JSON Parse Error: ' + parseErr.message
      };
    }

    if (typeof json === 'object' && json !== null) {
      if (json.success === undefined) {
        json.success = resp.ok;
      }
      if (!json.status) {
        json.status = resp.status;
      }
    }
    return json;
  } catch (netErr) {
    return {
      success: false,
      status: 0,
      data: null,
      error: 'Network connection failed: ' + netErr.message
    };
  }
}

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
  options.headers = Object.assign(authHeaders(options.method && options.method !== 'GET'), options.headers || {});
  if (options.body && typeof options.body === 'string' && !options.headers['Content-Type']) {
    options.headers['Content-Type'] = 'application/json';
  }

  var fullUrl;
  if (path.startsWith('http://') || path.startsWith('https://')) {
    fullUrl = path;
  } else {
    var base = getApiBaseUrl();
    var cleanPath = path.startsWith('/') ? path : ('/' + path);
    if (cleanPath.startsWith('/api/')) {
      cleanPath = cleanPath.substring(4);
    }
    fullUrl = base + cleanPath;
  }

  var result = await safeJsonFetch(fullUrl, options);

  // Fallback try to secondary endpoint (localhost:8080 <-> Apache public/index.php) if connection failed or 404
  if (!result.success && (result.status === 0 || result.status === 404 || (result.error && result.error.includes('failed')))) {
    var isCurrently8080 = fullUrl.includes(':8080');
    var fallbackBase = isCurrently8080
      ? (window.location.origin + '/my%20work/Atom/backend/public/index.php/api')
      : ('http://localhost:8080/api');
    var cleanPath2 = path.startsWith('/') ? path : ('/' + path);
    if (cleanPath2.startsWith('/api/')) cleanPath2 = cleanPath2.substring(4);
    var fallbackUrl = fallbackBase + cleanPath2;

    if (fallbackUrl !== fullUrl) {
      var fallbackResult = await safeJsonFetch(fallbackUrl, options);
      if (fallbackResult.success) {
        return fallbackResult;
      }
    }
  }

  if (result.status === 401) {
    handleAuthFailure();
    return { success: false, message: 'Session expired. Please log in again.', status: 401 };
  }

  return result;
}

window.getApiBaseUrl = getApiBaseUrl;
window.safeJsonFetch = safeJsonFetch;
window.apiFetch = apiFetch;

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

// ===== Advanced Toast & Alert Notification Suite =====

function playNotificationSound(type) {
  try {
    if (!window.AudioContext && !window.webkitAudioContext) return;
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    
    if (type === 'error') {
      osc.frequency.setValueAtTime(220, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(140, ctx.currentTime + 0.15);
    } else if (type === 'success') {
      osc.frequency.setValueAtTime(520, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(780, ctx.currentTime + 0.12);
    } else {
      osc.frequency.setValueAtTime(440, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(550, ctx.currentTime + 0.1);
    }
    gain.gain.setValueAtTime(0.04, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
    osc.start();
    osc.stop(ctx.currentTime + 0.16);
  } catch (e) {}
}

/**
 * Reusable animated glassmorphic toaster.
 * Types: success, error, warning, info, purple, cyan
 */
function showToast(message, type = 'info', duration = 3500) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:380px;pointer-events:none;';
    document.body.appendChild(container);
  }

  const themes = {
    success: { icon: '✓', color: '#10b981', bg: 'rgba(16,185,129,0.12)', border: 'rgba(16,185,129,0.4)', glow: 'rgba(16,185,129,0.2)', title: 'SUCCESS' },
    error:   { icon: '✕', color: '#ef4444', bg: 'rgba(239,68,68,0.12)', border: 'rgba(239,68,68,0.4)', glow: 'rgba(239,68,68,0.2)', title: 'ERROR' },
    warning: { icon: '⚠', color: '#f59e0b', bg: 'rgba(245,158,11,0.12)', border: 'rgba(245,158,11,0.4)', glow: 'rgba(245,158,11,0.2)', title: 'WARNING' },
    info:    { icon: 'ℹ', color: '#3b82f6', bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.4)', glow: 'rgba(59,130,246,0.2)', title: 'INFO' },
    cyan:    { icon: '⚡', color: '#06b6d4', bg: 'rgba(6,182,212,0.12)', border: 'rgba(6,182,212,0.4)', glow: 'rgba(6,182,212,0.2)', title: 'ATOM' },
    purple:  { icon: '✦', color: '#a855f7', bg: 'rgba(168,85,247,0.12)', border: 'rgba(168,85,247,0.4)', glow: 'rgba(168,85,247,0.2)', title: 'REASONING' }
  };

  const t = themes[type] || themes.info;
  playNotificationSound(type);

  const toast = document.createElement('div');
  toast.style.cssText = `pointer-events:auto;position:relative;display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border-radius:14px;background:#0c0f14;border:1px solid ${t.border};box-shadow:0 16px 40px rgba(0,0,0,0.7), 0 0 20px ${t.glow};opacity:0;transform:translateX(40px) scale(0.95);transition:all 0.3s cubic-bezier(0.16,1,0.3,1);overflow:hidden;backdrop-filter:blur(12px);`;
  
  toast.innerHTML = `
    <div style="flex:0 0 30px;width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;color:#fff;background:${t.color};box-shadow:0 2px 10px ${t.glow};">${t.icon}</div>
    <div style="flex:1;min-width:0;">
      <div style="font-size:10px;font-weight:800;letter-spacing:0.06em;color:${t.color};text-transform:uppercase;">${t.title}</div>
      <div style="font-size:12.5px;font-weight:500;color:#f0f4f8;margin-top:2px;line-height:1.45;">${escapeHtml(message)}</div>
    </div>
    <button aria-label="Dismiss" style="flex:0 0 auto;background:none;border:none;color:#64748b;cursor:pointer;font-size:16px;line-height:1;padding:2px 4px;border-radius:6px;transition:color 0.15s;">&times;</button>
    <div class="toast-progress" style="position:absolute;bottom:0;left:0;height:2.5px;width:100%;background:${t.color};opacity:0.8;transform-origin:left;transition:transform ${duration}ms linear;"></div>
  `;

  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(0) scale(1)';
    const bar = toast.querySelector('.toast-progress');
    if (bar) bar.style.transform = 'scaleX(0)';
  });

  const dismiss = () => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(40px) scale(0.95)';
    setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
  };

  const closeBtn = toast.querySelector('button');
  if (closeBtn) closeBtn.addEventListener('click', dismiss);
  setTimeout(dismiss, duration);

  return toast;
}

/**
 * Advanced Interactive Modal Prompter.
 * Replaces browser prompt() with a sleek glassmorphic modal.
 */
function showPromptModal({ title = 'Input Required', message = '', placeholder = '', defaultValue = '', confirmText = 'Submit', cancelText = 'Cancel' } = {}) {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(8,10,13,0.85);backdrop-filter:blur(8px);z-index:100000;display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;transition:opacity 0.2s ease;';
    
    overlay.innerHTML = `
      <div style="background:#11151c;border:1px solid #1e2838;box-shadow:0 24px 60px rgba(0,0,0,0.8), 0 0 30px rgba(6,182,212,0.15);border-radius:18px;max-width:440px;width:100%;padding:24px;transform:scale(0.95);transition:transform 0.2s cubic-bezier(0.16,1,0.3,1);">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
          <div style="width:32px;height:32px;border-radius:10px;background:rgba(6,182,212,0.15);border:1px solid rgba(6,182,212,0.3);color:#06b6d4;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:14px;">✎</div>
          <h3 style="font-size:16px;font-weight:800;color:#fff;margin:0;">${escapeHtml(title)}</h3>
        </div>
        ${message ? `<p style="font-size:12px;color:#94a3b8;margin:0 0 16px 0;line-height:1.5;">${escapeHtml(message)}</p>` : ''}
        <input type="text" id="modalPromptInput" value="${escapeHtml(defaultValue)}" placeholder="${escapeHtml(placeholder)}" style="width:100%;height:44px;padding:0 14px;background:#080a0d;border:1px solid #1e2838;border-radius:12px;color:#fff;font-size:13px;outline:none;margin-bottom:20px;box-sizing:border-box;transition:border-color 0.2s;">
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button id="modalPromptCancel" style="padding:9px 16px;border-radius:10px;background:#0c0f14;border:1px solid #1e2838;color:#94a3b8;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;">${escapeHtml(cancelText)}</button>
          <button id="modalPromptConfirm" style="padding:9px 18px;border-radius:10px;background:#10b981;border:none;color:#000;font-size:12px;font-weight:800;cursor:pointer;transition:all 0.15s;">${escapeHtml(confirmText)}</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
    const input = overlay.querySelector('#modalPromptInput');
    const confirmBtn = overlay.querySelector('#modalPromptConfirm');
    const cancelBtn = overlay.querySelector('#modalPromptCancel');
    const card = overlay.querySelector('div');

    requestAnimationFrame(() => {
      overlay.style.opacity = '1';
      card.style.transform = 'scale(1)';
      if (input) { input.focus(); input.select(); }
    });

    const close = (val) => {
      overlay.style.opacity = '0';
      card.style.transform = 'scale(0.95)';
      setTimeout(() => {
        if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        resolve(val);
      }, 200);
    };

    confirmBtn.addEventListener('click', () => close(input.value.trim() || null));
    cancelBtn.addEventListener('click', () => close(null));
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(null); });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') close(input.value.trim() || null);
      if (e.key === 'Escape') close(null);
    });
  });
}

/**
 * Advanced Confirmation Modal.
 * Replaces browser confirm() with a sleek glassmorphic modal.
 */
function showConfirmModal({ title = 'Confirm Action', message = 'Are you sure you want to proceed?', confirmText = 'Confirm', cancelText = 'Cancel', type = 'danger' } = {}) {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(8,10,13,0.85);backdrop-filter:blur(8px);z-index:100000;display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;transition:opacity 0.2s ease;';
    
    const isDanger = (type === 'danger');
    const accentColor = isDanger ? '#ef4444' : '#10b981';
    const accentBg = isDanger ? 'rgba(239,68,68,0.15)' : 'rgba(16,185,129,0.15)';
    const accentBorder = isDanger ? 'rgba(239,68,68,0.35)' : 'rgba(16,185,129,0.35)';
    const icon = isDanger ? '⚠' : '✓';

    overlay.innerHTML = `
      <div style="background:#11151c;border:1px solid #1e2838;box-shadow:0 24px 60px rgba(0,0,0,0.8), 0 0 30px ${accentBg};border-radius:18px;max-width:440px;width:100%;padding:24px;transform:scale(0.95);transition:transform 0.2s cubic-bezier(0.16,1,0.3,1);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
          <div style="width:36px;height:36px;border-radius:11px;background:${accentBg};border:1px solid ${accentBorder};color:${accentColor};display:flex;align-items:center;justify-content:center;font-weight:900;font-size:16px;">${icon}</div>
          <h3 style="font-size:16px;font-weight:800;color:#fff;margin:0;">${escapeHtml(title)}</h3>
        </div>
        <p style="font-size:12.5px;color:#94a3b8;margin:0 0 20px 0;line-height:1.5;">${escapeHtml(message)}</p>
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button id="modalConfirmCancel" style="padding:9px 16px;border-radius:10px;background:#0c0f14;border:1px solid #1e2838;color:#94a3b8;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.15s;">${escapeHtml(cancelText)}</button>
          <button id="modalConfirmAction" style="padding:9px 18px;border-radius:10px;background:${accentColor};border:none;color:#fff;font-size:12px;font-weight:800;cursor:pointer;transition:all 0.15s;">${escapeHtml(confirmText)}</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
    const confirmBtn = overlay.querySelector('#modalConfirmAction');
    const cancelBtn = overlay.querySelector('#modalConfirmCancel');
    const card = overlay.querySelector('div');

    requestAnimationFrame(() => {
      overlay.style.opacity = '1';
      card.style.transform = 'scale(1)';
      confirmBtn.focus();
    });

    const close = (val) => {
      overlay.style.opacity = '0';
      card.style.transform = 'scale(0.95)';
      setTimeout(() => {
        if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        resolve(val);
      }, 200);
    };

    confirmBtn.addEventListener('click', () => close(true));
    cancelBtn.addEventListener('click', () => close(false));
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(false); });
    document.addEventListener('keydown', function escHandler(e) {
      if (e.key === 'Escape') {
        document.removeEventListener('keydown', escHandler);
        close(false);
      }
    });
  });
}

/**
 * Advanced Alert Modal.
 * Replaces browser alert() with a sleek glassmorphic modal.
 */
function showAlertModal({ title = 'Notification', message = '', buttonText = 'OK', type = 'info' } = {}) {
  return showConfirmModal({
    title,
    message,
    confirmText: buttonText,
    cancelText: '',
    type: type === 'error' ? 'danger' : 'success'
  }).then(() => {});
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
