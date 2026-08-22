<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — AI Providers</title>
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
        <h1 class="text-3xl font-black tracking-tight">AI PROVIDER SYSTEM</h1>
        <p class="text-xs text-gray-500 mt-1">Configure credentials, keys, endpoints, and default parameters for multi-provider routing.</p>
      </div>

      <!-- Provider configuration cards grid -->
      <div id="providerGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="text-center py-12 text-gray-500 text-xs col-span-full">Retrieving provider settings...</div>
      </div>
    </main>
  </div>

  <script src="/admin/js/shared.js"></script>
  <script>
    async function loadProviders() {
      const grid = document.getElementById('providerGrid');
      try {
        const json = await apiFetch('/analytics/providers');
        const active = json.data && json.data.active;

        if (json.success && json.data && json.data.providers) {
          const providers = json.data.providers;
          grid.innerHTML = Object.keys(providers).map(key => {
            const p = providers[key];
            const isActive = active === key;
            const statusText = isActive ? 'ACTIVE' : (p.configured ? 'CONFIGURED' : 'OFFLINE');
            const onlineColor = isActive ? 'text-emerald-400' : (p.configured ? 'text-amber-400' : 'text-red-400');

            return `
              <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg space-y-5 ${isActive ? 'border-emerald-500/40' : 'hover:border-emerald-500/20'} transition-all">
                <div class="flex items-center justify-between border-b border-[#1e2838] pb-4">
                  <div class="flex items-center gap-3">
                    <span class="w-3 h-3 rounded-full ${isActive ? 'bg-emerald-500' : (p.configured ? 'bg-amber-400' : 'bg-red-500')}"></span>
                    <h3 class="font-bold text-white text-base">${escapeHtml(key.toUpperCase())}</h3>
                  </div>
                  <span class="text-[9px] font-black uppercase tracking-wider ${onlineColor}">● ${statusText}</span>
                </div>
                <div class="space-y-3 text-xs">
                  <div class="space-y-1">
                    <span class="text-gray-500">Endpoint API URL</span>
                    <p class="font-mono text-gray-300 break-all select-all">${escapeHtml(p.endpoint || 'N/A')}</p>
                  </div>
                  <div class="space-y-1">
                    <span class="text-gray-500">Model Name</span>
                    <p class="font-mono text-gray-300">${escapeHtml(p.model || 'N/A')}</p>
                  </div>
                  <div class="space-y-1">
                    <span class="text-gray-500">API Credentials</span>
                    <p class="text-gray-500 font-mono">${p.key ? '●●●●●●●●●●●● (Protected)' : 'Not configured'}</p>
                  </div>
                </div>
              </div>
            `;
          }).join('');
        } else {
          grid.innerHTML = '<div class="text-center py-12 text-gray-500 text-xs col-span-full">No active providers configured.</div>';
        }
      } catch (e) {
        grid.innerHTML = '<div class="text-center py-12 text-red-400 text-xs col-span-full">Failed to load providers.</div>';
      }
    }
    loadProviders();
  </script>
</body>
</html>
