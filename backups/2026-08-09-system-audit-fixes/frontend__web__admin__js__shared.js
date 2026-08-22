// Shared JavaScript logic for ATOM Admin Control panel UI collapsibility, global search overlays, and event handlers.

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
  query = query.trim();
  if (query.length < 2) {
    resultsEl.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">Type at least 2 characters to start querying...</div>';
    return;
  }

  resultsEl.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">Querying ATOM database...</div>';

  try {
    const resp = await fetch('http://localhost:8080/api/settings/global_search?query=' + encodeURIComponent(query));
    const json = await resp.json();

    if (json.success && json.data && json.data.length > 0) {
      resultsEl.innerHTML = json.data.map(item => `
        <div class="p-3 rounded-xl bg-[#080a0d] border border-[#1e2838] hover:border-emerald-500/20 transition-all space-y-1">
          <div class="flex items-center justify-between text-[10px]">
            <span class="px-2 py-0.5 rounded font-bold uppercase bg-emerald-500/10 text-emerald-400">${item.type}</span>
            <span class="text-gray-500">${item.source || 'General'}</span>
          </div>
          <h4 class="text-white font-bold text-xs">${item.title}</h4>
          <p class="text-[11px] text-gray-400 truncate">${item.content || ''}</p>
        </div>
      `).join('');
    } else {
      resultsEl.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">No records found matching "' + query + '"</div>';
    }
  } catch (e) {
    resultsEl.innerHTML = '<div class="text-center py-8 text-red-400 text-xs">Query failed. Please verify API endpoints.</div>';
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
    success: { icon: '✓', accent: '#10b981', bg: 'rgba(16,185,129,0.12)', border: 'rgba(16,185,129,0.45)', label: 'SUCCESS' },
    error:   { icon: '✕', accent: '#ef4444', bg: 'rgba(239,68,68,0.12)', border: 'rgba(239,68,68,0.45)', label: 'ERROR' },
    warning: { icon: '⚠', accent: '#f59e0b', bg: 'rgba(245,158,11,0.12)', border: 'rgba(245,158,11,0.45)', label: 'WARNING' },
    info:    { icon: 'ℹ', accent: '#3b82f6', bg: 'rgba(59,130,246,0.12)', border: 'rgba(59,130,246,0.45)', label: 'INFO' }
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
    + message.replace(/</g, '&lt;') + '</div></div>'
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
