<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Settings</title>
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
        <h1 class="text-3xl font-black tracking-tight">SYSTEM SETTINGS</h1>
        <p class="text-xs text-gray-500 mt-1">Configure general, personalization, appearance, and optimization parameters.</p>
      </div>

      <!-- Settings panel tabs -->
      <div class="bg-[#11151c] border border-[#1e2838] p-6 rounded-2xl shadow-lg space-y-6">
        <h3 class="font-bold text-white text-sm border-b border-[#1e2838] pb-3">Personalization Settings</h3>
        
        <form id="settingsForm" class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs" onsubmit="saveSettings(event)">
          <div class="space-y-1">
            <label class="text-gray-500 font-semibold block">Full Name</label>
            <input type="text" id="fullName" name="full_name" class="w-full h-10 px-3 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500/50">
          </div>
          <div class="space-y-1">
            <label class="text-gray-500 font-semibold block">Preferred Name</label>
            <input type="text" id="prefName" name="preferred_name" class="w-full h-10 px-3 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500/50">
          </div>
          <div class="space-y-1">
            <label class="text-gray-500 font-semibold block">Preferred Language</label>
            <input type="text" id="prefLang" name="preferred_language" class="w-full h-10 px-3 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500/50">
          </div>
          <div class="space-y-1">
            <label class="text-gray-500 font-semibold block">Timezone</label>
            <input type="text" id="timeZone" name="timezone" class="w-full h-10 px-3 rounded-xl bg-[#080a0d] border border-[#1e2838] text-white focus:outline-none focus:border-emerald-500/50">
          </div>
          <div class="md:col-span-2 pt-4">
            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-emerald-500 hover:bg-emerald-600 text-white shadow shadow-emerald-500/10 transition-all">
              Save Settings
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <script src="<?= $getBaseUrl() ?>/admin/js/shared.js"></script>
  <script>
    async function loadSettings() {
      try {
        const json = await apiFetch('/profile');
        if (json.success && json.data && json.data.profile) {
          const p = json.data.profile;
          document.getElementById('fullName').value = p.full_name || '';
          document.getElementById('prefName').value = p.preferred_name || '';
          document.getElementById('prefLang').value = p.preferred_language || '';
          document.getElementById('timeZone').value = p.timezone || '';
        }
      } catch (e) {}
    }

    async function saveSettings(event) {
      event.preventDefault();
      const data = {
        full_name: document.getElementById('fullName').value,
        preferred_name: document.getElementById('prefName').value,
        preferred_language: document.getElementById('prefLang').value,
        timezone: document.getElementById('timeZone').value
      };

      try {
        const json = await apiFetch('/profile', {
          method: 'POST',
          body: JSON.stringify(data)
        });
        if (json.success) {
          showToast('Settings updated successfully!', 'success');
        } else {
          showToast('Failed to save settings: ' + (json.message || 'Unknown error'), 'error');
        }
      } catch (e) {
        showToast('Connection error. Verify backend status.', 'error');
      }
    }

    loadSettings();
  </script>
</body>
</html>
