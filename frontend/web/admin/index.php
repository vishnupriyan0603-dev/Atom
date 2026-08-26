<?php
require_once __DIR__ . '/../bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ATOM Control — Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js"></script>
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
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black tracking-tight">ATOM BRAIN CONTROL CENTER</h1>
          <p class="text-xs text-gray-500 mt-1">Status: Operational. Active reasoning active.</p>
        </div>
        <div class="flex gap-2">
          <button onclick="runDeduplication()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-500 hover:bg-emerald-600 text-white shadow shadow-emerald-500/10 transition-all">
            Optimize Database
          </button>
        </div>
      </div>

      <!-- Top Score and Quick stats -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Health Score panel -->
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 flex items-center justify-between shadow-lg">
          <div>
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Brain Health</h3>
            <span class="text-4xl font-extrabold text-white mt-2 block"><?php echo $stats['health_score']; ?>%</span>
            <p class="text-[10px] text-gray-500 mt-2">Deduplicated & verified knowledge.</p>
          </div>
          <!-- Simple SVG Circular Indicator -->
          <div class="relative w-20 h-20">
            <svg class="w-full h-full transform -rotate-90">
              <circle cx="40" cy="40" r="32" stroke="#1e2838" stroke-width="6" fill="transparent" />
              <circle cx="40" cy="40" r="32" stroke="#10b981" stroke-width="6" fill="transparent"
                      stroke-dasharray="200" stroke-dashoffset="<?php echo 200 - (200 * $stats['health_score'] / 100); ?>" />
            </svg>
          </div>
        </div>

        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg">
          <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Duplicates Cleaned</h3>
          <span class="text-4xl font-extrabold text-[#f59e0b] mt-2 block"><?php echo number_format($stats['duplicate_count']); ?></span>
          <p class="text-[10px] text-gray-500 mt-2">Avoided redundant training records.</p>
        </div>

        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg">
          <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Optimized Canonical Rows</h3>
          <span class="text-4xl font-extrabold text-emerald-400 mt-2 block"><?php echo number_format($stats['optimized_count']); ?></span>
          <p class="text-[10px] text-gray-500 mt-2">Merged equivalent question groups.</p>
        </div>
      </div>

      <!-- Core RAG and stats grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-5 flex flex-col justify-between shadow">
          <span class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Knowledge Chunks</span>
          <span class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['knowledge_count']); ?></span>
        </div>
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-5 flex flex-col justify-between shadow">
          <span class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Original Documents</span>
          <span class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['document_count']); ?></span>
        </div>
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-5 flex flex-col justify-between shadow">
          <span class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">Training Q&A</span>
          <span class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['training_count']); ?></span>
        </div>
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-5 flex flex-col justify-between shadow">
          <span class="text-[10px] uppercase font-bold text-gray-500 tracking-wider">AI Chats</span>
          <span class="text-2xl font-black text-white mt-2"><?php echo number_format($stats['conversations']); ?></span>
        </div>
      </div>

      <!-- Central status visualization and charts -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Visual brain network representation container -->
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 lg:col-span-2 shadow-lg flex flex-col justify-between">
          <div class="border-b border-[#1e2838] pb-4 mb-4">
            <h3 class="font-bold text-white text-sm">RAG & Knowledge Growth over time</h3>
          </div>
          <div class="h-64">
            <canvas id="growthChart" class="w-full h-full"></canvas>
          </div>
        </div>

        <!-- Right System detail lists -->
        <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg flex flex-col justify-between">
          <div class="border-b border-[#1e2838] pb-4 mb-4">
            <h3 class="font-bold text-white text-sm">Self-Learning Logs</h3>
          </div>
          <div class="flex-1 overflow-y-auto space-y-3 pr-2 text-xs" id="selfLearningLogs">
            <div class="text-center py-8 text-gray-500 text-[10px]">No learning history logged. Waiting for cross-model training cycles...</div>
          </div>
        </div>
      </div>

      <!-- ATOM 3D NEURAL BRAIN MODEL -->
      <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#1e2838] pb-4 mb-4">
          <div>
            <h3 class="font-bold text-white text-sm flex items-center gap-2"><span>🧠</span> ATOM Neural Brain Model</h3>
            <p class="text-[10px] text-gray-500 mt-1">Live 3D visualization of the learning state — color reflects brain health, pulses reflect active recall.</p>
          </div>
          <div class="flex items-center gap-3 text-[10px]">
            <span class="flex items-center gap-1 text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span> Healthy</span>
            <span class="flex items-center gap-1 text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span> Learning</span>
            <span class="flex items-center gap-1 text-gray-400"><span class="w-2.5 h-2.5 rounded-full bg-rose-400 inline-block"></span> Needs Review</span>
          </div>
        </div>
        <div id="brainModelContainer" class="relative w-full h-80 rounded-xl overflow-hidden bg-[#080a0d]">
          <canvas id="brainCanvas" class="w-full h-full block"></canvas>
          <div class="absolute bottom-3 left-3 flex gap-4 text-[10px] text-gray-400">
            <span>Neurons: <span class="text-white font-bold" id="brainNeuronCount">0</span></span>
            <span>Synapses Firing: <span class="text-emerald-400 font-bold" id="brainSynapseCount">0</span></span>
          </div>
        </div>
      </div>

      <!-- ATOM SELF-LEARNING & HUMAN SAFETY GATE SECTION -->
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
              <span>🛡️</span> ATOM Safety Gate &amp; Self-Improvement Engine
            </h2>
            <p class="text-xs text-gray-400 mt-1">Autonomous flaw detection, A/B sandbox benchmarking, and mandatory human authorization gate</p>
          </div>
          <button onclick="loadSafetyGateData()" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-[#1e2838] hover:bg-[#2a384e] text-gray-300 transition">
            Refresh Safety Gate
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Pending Approvals Widget -->
          <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg lg:col-span-1 flex flex-col">
            <div class="flex items-center justify-between border-b border-[#1e2838] pb-4 mb-4">
              <h3 class="font-bold text-sky-400 text-sm flex items-center gap-2">
                <span>🛡️</span> Pending Safety Approvals
              </h3>
              <span id="pendingCountBadge" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-400 border border-amber-500/30">0 Pending</span>
            </div>
            <div class="flex-1 overflow-y-auto space-y-3 max-h-72" id="pendingApprovalsList">
              <div class="text-center py-6 text-gray-500 text-xs">Loading pending approvals...</div>
            </div>
          </div>

          <!-- A/B Experiments Widget -->
          <div class="bg-[#11151c] border border-[#1e2838] rounded-2xl p-6 shadow-lg lg:col-span-2 flex flex-col">
            <div class="border-b border-[#1e2838] pb-4 mb-4 flex items-center justify-between">
              <h3 class="font-bold text-emerald-400 text-sm flex items-center gap-2">
                <span>🧪</span> Sandbox A/B Benchmarks &amp; Experiments
              </h3>
              <span class="text-[10px] text-gray-400">Min. Threshold: +5.0%</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs text-gray-300">
                <thead class="text-[10px] uppercase font-bold text-gray-500 border-b border-[#1e2838]">
                  <tr>
                    <th class="pb-2">Title</th>
                    <th class="pb-2">Target</th>
                    <th class="pb-2">Baseline</th>
                    <th class="pb-2">Candidate</th>
                    <th class="pb-2">Improvement</th>
                    <th class="pb-2">Status</th>
                  </tr>
                </thead>
                <tbody id="experimentsTableBody" class="divide-y divide-[#1e2838]">
                  <tr><td colspan="6" class="text-center py-6 text-gray-500">Loading experiments...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="<?= $getBaseUrl() ?>/admin/js/shared.js"></script>
  <script>
    // Growth Chart rendering
    const ctx = document.getElementById('growthChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
        datasets: [{
          label: 'Knowledge Chunks',
          data: [20, 80, 250, 450, 800, 1500, 3400, <?php echo $stats['knowledge_count']; ?>],
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.05)',
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: '#16202e' }, ticks: { color: '#566478' } },
          y: { grid: { color: '#16202e' }, ticks: { color: '#566478' } }
        }
      }
    });

    async function loadSelfLearningLogs() {
      const logsEl = document.getElementById('selfLearningLogs');
      try {
        const json = await apiFetch('/analytics/learning-history', { method: 'GET' });
        if (json.success && json.data && json.data.length > 0) {
          logsEl.innerHTML = json.data.map(log => `
            <div class="p-3 rounded-xl bg-[#080a0d] border border-[#1e2838] space-y-1">
              <div class="flex items-center justify-between text-[9px]">
                <span class="text-emerald-400 font-bold uppercase tracking-wider">${escapeHtml(log.topic)}</span>
                <span class="text-gray-500">${new Date(log.created_at).toLocaleTimeString()}</span>
              </div>
              <p class="text-gray-300 text-[11px] leading-relaxed">${escapeHtml(log.action_text)}</p>
            </div>
          `).join('');
        } else {
          logsEl.innerHTML = '<div class="text-center py-8 text-gray-500 text-[10px]">No learning history logged yet.</div>';
        }
      } catch (e) {
        logsEl.innerHTML = '<div class="text-center py-8 text-red-400 text-[10px]">Failed to load learning history.</div>';
      }
    }
    loadSelfLearningLogs();

    async function runDeduplication() {
      try {
        const json = await apiFetch('/analytics/optimize-training', { method: 'POST' });
        showToast(json.message || 'Optimized successfully', 'success');
        window.location.reload();
      } catch (e) {
        showToast('Optimization command failed', 'error');
      }
    }

    async function loadSafetyGateData() {
      const approvalsListEl = document.getElementById('pendingApprovalsList');
      const badgeEl = document.getElementById('pendingCountBadge');
      const expBodyEl = document.getElementById('experimentsTableBody');

      try {
        const appRes = await apiFetch('/improvement/approvals');
        if (appRes && appRes.data) {
          badgeEl.textContent = `${appRes.data.length} Pending`;
          if (appRes.data.length > 0) {
            approvalsListEl.innerHTML = appRes.data.map(item => `
              <div class="p-3.5 rounded-xl bg-[#080a0d] border border-[#1e2838] space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-sky-400">${escapeHtml(item.action)}</span>
                  <span class="text-[9px] text-gray-500">${escapeHtml(item.created_at || '')}</span>
                </div>
                <p class="text-xs text-gray-300">${escapeHtml(item.reason || 'Candidate experiment promotion')}</p>
                <div class="flex items-center justify-end gap-2 pt-1">
                  <button onclick="approveSafetyItem(${item.id})" class="px-2.5 py-1 rounded text-[11px] font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition">Approve ✅</button>
                  <button onclick="rejectSafetyItem(${item.id})" class="px-2.5 py-1 rounded text-[11px] font-bold bg-rose-600 hover:bg-rose-500 text-white transition">Reject ❌</button>
                </div>
              </div>
            `).join('');
          } else {
            approvalsListEl.innerHTML = '<div class="text-center py-6 text-emerald-400 text-xs font-medium">All candidate promotions authorized!</div>';
          }
        }

        const expRes = await apiFetch('/improvement/experiments');
        if (expRes && expRes.data && expRes.data.length > 0) {
          expBodyEl.innerHTML = expRes.data.map(exp => `
            <tr class="hover:bg-[#16202e]/50 transition">
              <td class="py-2.5 font-medium text-white">${escapeHtml(exp.title)}</td>
              <td class="py-2.5 text-gray-400">${escapeHtml(exp.target_component)}</td>
              <td class="py-2.5 text-gray-300">${(exp.baseline_score * 100).toFixed(1)}%</td>
              <td class="py-2.5 text-emerald-400 font-semibold">${(exp.candidate_score * 100).toFixed(1)}%</td>
              <td class="py-2.5 text-emerald-400 font-bold">+${parseFloat(exp.improvement_pct).toFixed(1)}%</td>
              <td class="py-2.5">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold ${exp.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'}">${escapeHtml(exp.status)}</span>
              </td>
            </tr>
          `).join('');
        } else {
          expBodyEl.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-gray-500">No active or past experiments found.</td></tr>';
        }
      } catch (e) {
        approvalsListEl.innerHTML = '<div class="text-center py-6 text-red-400 text-xs">Failed to load approvals.</div>';
        expBodyEl.innerHTML = '<tr><td colspan="6" class="text-center py-6 text-red-400">Failed to load experiments.</td></tr>';
      }
    }

    async function approveSafetyItem(id) {
      try {
        const json = await apiFetch(`/improvement/approvals/${id}/approve`, { method: 'POST', body: JSON.stringify({ approver: 'WebAdmin' }) });
        showToast(json.message || 'Approved experiment promotion', 'success');
        loadSafetyGateData();
      } catch (e) {
        showToast('Approval action failed', 'error');
      }
    }

    async function rejectSafetyItem(id) {
      try {
        const json = await apiFetch(`/improvement/approvals/${id}/reject`, { method: 'POST', body: JSON.stringify({ approver: 'WebAdmin', reason: 'Rejected from web admin UI' }) });
        showToast(json.message || 'Rejected experiment promotion', 'info');
        loadSafetyGateData();
      } catch (e) {
        showToast('Rejection action failed', 'error');
      }
    }

    loadSafetyGateData();

    // ── ATOM 3D Neural Brain Model ─────────────────────────────────────────
    // Procedurally generated particle brain — no external 3D asset required.
    // Color = learning health (red → amber → green). Pulse = simulated recall.
    (function initBrainModel() {
      const container = document.getElementById('brainModelContainer');
      const canvas = document.getElementById('brainCanvas');
      if (!container || !canvas || typeof THREE === 'undefined') return;

      const healthScore = <?php echo (int)$stats['health_score']; ?>;
      const knowledgeCount = <?php echo (int)$stats['knowledge_count']; ?>;

      const pointCount = Math.min(3000, Math.max(700, knowledgeCount || 700));
      document.getElementById('brainNeuronCount').textContent = pointCount.toLocaleString();

      const scene = new THREE.Scene();
      const camera = new THREE.PerspectiveCamera(50, container.clientWidth / container.clientHeight, 0.1, 100);
      camera.position.set(0, 0, 7);

      const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
      renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
      renderer.setSize(container.clientWidth, container.clientHeight);

      // 0% health → red (hue 0); 100% health → green (hue 140)
      const baseHue = Math.max(0, Math.min(140, ((healthScore - 50) / 50) * 140));

      const positions = new Float32Array(pointCount * 3);
      const colors = new Float32Array(pointCount * 3);
      const phases = new Float32Array(pointCount);
      const tmpColor = new THREE.Color();

      const R = 2.1;
      for (let i = 0; i < pointCount; i++) {
        const theta = 2 * Math.PI * Math.random();
        const phi = Math.acos(1 - 2 * Math.random());

        let x = Math.sin(phi) * Math.cos(theta);
        let y = Math.cos(phi);
        let z = Math.sin(phi) * Math.sin(theta);

        // Cortex fold displacement — layered sine waves stand in for gyri/sulci noise
        const fold = Math.sin(theta * 6 + phi * 5) * Math.cos(phi * 8 - theta * 3) * 0.09
                   + Math.sin(theta * 3 - phi * 7) * 0.05;
        const r = R * (1 + fold);

        x *= r; y *= r * 0.92; z *= r * 0.82;

        // Split into two hemispheres around a central longitudinal fissure
        const gap = 0.12;
        x += x >= 0 ? gap : -gap;

        // Taper the lower region into a brain-stem cluster
        if (y < -R * 0.55) { x *= 0.35; z *= 0.35; }

        positions[i * 3] = x;
        positions[i * 3 + 1] = y;
        positions[i * 3 + 2] = z;

        const lightness = Math.min(0.75, Math.max(0.25, 0.35 + Math.max(0, fold) * 2.2));
        const hue = (baseHue + fold * 40 + 360) % 360;
        tmpColor.setHSL(hue / 360, 0.75, lightness);
        colors[i * 3] = tmpColor.r;
        colors[i * 3 + 1] = tmpColor.g;
        colors[i * 3 + 2] = tmpColor.b;

        phases[i] = Math.random() * Math.PI * 2;
      }

      const geometry = new THREE.BufferGeometry();
      geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
      geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

      const material = new THREE.PointsMaterial({
        size: 0.045,
        vertexColors: true,
        transparent: true,
        opacity: 0.9,
        sizeAttenuation: true,
        depthWrite: false,
      });

      const brainPoints = new THREE.Points(geometry, material);

      // Synaptic connection lines — each hub neuron joins its 2 nearest neighbors.
      // Warm (pink) = close/strong bond, cool (blue) = far/weak bond.
      const maxConnectDist = 0.55;
      const hubStep = Math.max(1, Math.floor(pointCount / 380));
      const linePositions = [];
      const lineColors = [];
      const lineColor = new THREE.Color();

      for (let hub = 0; hub < pointCount; hub += hubStep) {
        const hx = positions[hub * 3], hy = positions[hub * 3 + 1], hz = positions[hub * 3 + 2];
        let bestIdx = -1, bestDist = maxConnectDist;
        let bestIdx2 = -1, bestDist2 = maxConnectDist;
        for (let j = 0; j < pointCount; j++) {
          if (j === hub) continue;
          const dx = positions[j * 3] - hx, dy = positions[j * 3 + 1] - hy, dz = positions[j * 3 + 2] - hz;
          const d = Math.sqrt(dx * dx + dy * dy + dz * dz);
          if (d < bestDist) { bestDist2 = bestDist; bestIdx2 = bestIdx; bestDist = d; bestIdx = j; }
          else if (d < bestDist2) { bestDist2 = d; bestIdx2 = j; }
        }
        [[bestIdx, bestDist], [bestIdx2, bestDist2]].forEach(([idx, dist]) => {
          if (idx === -1) return;
          const t = Math.min(1, dist / maxConnectDist); // 0 = close/warm, 1 = far/cool
          const hue = 340 - t * 130; // 340 warm pink → 210 cool blue
          const lightness = Math.max(0.22, 0.62 - t * 0.28);
          lineColor.setHSL(((hue % 360) + 360) % 360 / 360, 0.7, lightness);

          linePositions.push(hx, hy, hz, positions[idx * 3], positions[idx * 3 + 1], positions[idx * 3 + 2]);
          lineColors.push(lineColor.r, lineColor.g, lineColor.b, lineColor.r, lineColor.g, lineColor.b);
        });
      }

      const lineGeometry = new THREE.BufferGeometry();
      lineGeometry.setAttribute('position', new THREE.Float32BufferAttribute(linePositions, 3));
      lineGeometry.setAttribute('color', new THREE.Float32BufferAttribute(lineColors, 3));
      const lineMaterial = new THREE.LineBasicMaterial({ vertexColors: true, transparent: true, opacity: 0.45 });
      const synapseLines = new THREE.LineSegments(lineGeometry, lineMaterial);

      const brainGroup = new THREE.Group();
      brainGroup.add(brainPoints);
      brainGroup.add(synapseLines);
      scene.add(brainGroup);

      const light = new THREE.PointLight(0xffffff, 0.6);
      light.position.set(3, 3, 5);
      scene.add(light);

      // A subset of points act as "firing synapses" — brighter pulsing flashes
      const synapseCount = Math.min(120, Math.floor(pointCount * 0.06));
      document.getElementById('brainSynapseCount').textContent = synapseCount;
      const synapseIndices = [];
      for (let i = 0; i < synapseCount; i++) {
        synapseIndices.push(Math.floor(Math.random() * pointCount));
      }

      const colorAttr = geometry.getAttribute('color');
      const baseColors = colors.slice();

      let frame = 0;
      function animate() {
        frame++;
        const t = frame * 0.02;

        brainGroup.rotation.y += 0.0022;
        brainGroup.rotation.x = Math.sin(t * 0.15) * 0.08;

        for (let k = 0; k < synapseIndices.length; k++) {
          const idx = synapseIndices[k];
          const pulse = (Math.sin(t * 2 + phases[idx]) + 1) / 2;
          const boost = 1 + pulse * 1.6;
          colorAttr.array[idx * 3]     = Math.min(1, baseColors[idx * 3] * boost);
          colorAttr.array[idx * 3 + 1] = Math.min(1, baseColors[idx * 3 + 1] * boost);
          colorAttr.array[idx * 3 + 2] = Math.min(1, baseColors[idx * 3 + 2] * boost);
        }
        colorAttr.needsUpdate = true;

        renderer.render(scene, camera);
        requestAnimationFrame(animate);
      }
      animate();

      window.addEventListener('resize', () => {
        if (!container.clientWidth || !container.clientHeight) return;
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
      });
    })();
  </script>
</body>
</html>
