<?php
$pageTitle = "Plugins & Skills Hub";
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Atom Admin - Plugins & Skills</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css">
  <style>
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-[#0b0f17] text-gray-100 min-h-screen flex">
  <?php include __DIR__ . '/components/sidebar.php'; ?>

  <main class="flex-1 p-8 overflow-y-auto">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">Plugins & Skills Hub</h1>
        <p class="text-sm text-gray-400 mt-1">Modular skill registration, permission control, and tool execution bindings</p>
      </div>
      <button onclick="loadSkills()" class="px-4 py-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-xl text-xs font-semibold hover:bg-emerald-500/20 transition-all">
        Refresh Hub
      </button>
    </div>

    <!-- Skills Grid -->
    <div id="skillsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div class="col-span-full text-center py-12 text-gray-500 text-xs">Loading skills manifest...</div>
    </div>
  </main>

  <script>
    async function loadSkills() {
      const grid = document.getElementById('skillsGrid');
      try {
        const res = await fetch('/api/v1/skills');
        const json = await res.json();
        const data = json.data || [];

        if (data.length === 0) {
          grid.innerHTML = `<div class="col-span-full text-center py-12 text-gray-500 text-xs">No skills registered</div>`;
          return;
        }

        grid.innerHTML = data.map(skill => {
          const isEnabled = skill.enabled;
          const statusBadge = isEnabled
            ? '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">ENABLED</span>'
            : '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-500/10 text-gray-400 border border-gray-500/20">DISABLED</span>';

          const toolsList = (skill.tools || []).map(t => `<span class="px-2 py-0.5 bg-[#0b0f17] border border-[#1e2736] rounded text-[10px] font-mono text-gray-300">${t}</span>`).join(' ');

          return `
            <div class="bg-[#131924] border border-[#1e2736] rounded-2xl p-6 flex flex-col justify-between hover:border-gray-700 transition-all">
              <div>
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-base font-bold text-white uppercase tracking-wider">${skill.name}</h3>
                  ${statusBadge}
                </div>
                <p class="text-xs text-gray-400 leading-relaxed mb-4">${skill.description || 'No description provided'}</p>
                <div class="space-y-2 mb-4">
                  <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Attached Tools:</div>
                  <div class="flex flex-wrap gap-1.5">${toolsList || '<span class="text-xs text-gray-600">None</span>'}</div>
                </div>
              </div>
              <div class="pt-4 border-t border-[#1e2736] flex items-center justify-between text-xs text-gray-500">
                <span>v${skill.version}</span>
                <button onclick="toggleSkill('${skill.name}', ${!isEnabled})" class="px-3 py-1.5 rounded-xl font-semibold text-xs transition-all ${isEnabled ? 'bg-rose-500/10 text-rose-400 hover:bg-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'}">
                  ${isEnabled ? 'Disable Skill' : 'Enable Skill'}
                </button>
              </div>
            </div>
          `;
        }).join('');
      } catch (err) {
        grid.innerHTML = `<div class="col-span-full text-center py-12 text-rose-400 text-xs">Failed to load skills</div>`;
      }
    }

    async function toggleSkill(name, enable) {
      const endpoint = enable ? `/api/v1/skills/${name}/enable` : `/api/v1/skills/${name}/disable`;
      await fetch(endpoint, { method: 'POST' });
      loadSkills();
    }

    document.addEventListener('DOMContentLoaded', loadSkills);
  </script>
</body>
</html>
